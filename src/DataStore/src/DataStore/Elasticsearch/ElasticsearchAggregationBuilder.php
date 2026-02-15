<?php

declare(strict_types=1);

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Elasticsearch;

use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\Rql\Node\AggregateFunctionNode;
use rollun\datastore\Rql\RqlQuery;
use Xiag\Rql\Parser\Node\SelectNode;
use Xiag\Rql\Parser\Node\SortNode;
use Xiag\Rql\Parser\Query;

/**
 * Builds Elasticsearch aggregations from RQL query.
 *
 * This class is responsible for:
 * - Building metric aggregations (count, sum, avg, min, max)
 * - Building composite aggregations for GROUP BY
 * - Processing SELECT with aggregate functions
 * - Building group sources for composite aggregation
 *
 * Analogous to SqlQueryBuilder handling of GROUP BY and aggregate functions.
 */
final class ElasticsearchAggregationBuilder
{
    public function __construct(
        private readonly string $identifier = 'id'
    ) {
    }

    /**
     * Check if query should use native Elasticsearch aggregations.
     *
     * Returns true if:
     * - Query has GROUP BY clause, OR
     * - Query has aggregate SELECT without plain fields
     *
     * @param Query $query
     * @return bool
     */
    public function shouldUseNativeAggregations(Query $query): bool
    {
        $groupFields = $this->extractGroupByFields($query);
        if ($groupFields !== []) {
            return true;
        }

        $selectNode = $query->getSelect();

        return $this->hasAggregateSelect($selectNode) && !$this->hasPlainSelectFields($selectNode);
    }

    /**
     * Extract GROUP BY field names from RQL query.
     *
     * @param Query $query
     * @return string[]
     */
    public function extractGroupByFields(Query $query): array
    {
        if (!$query instanceof RqlQuery || $query->getGroupBy() === null) {
            return [];
        }

        $fields = [];
        foreach ($query->getGroupBy()->getFields() as $field) {
            if (!is_string($field) || $field === '') {
                continue;
            }

            $fields[] = $field;
        }

        return array_values(array_unique($fields));
    }

    /**
     * Check if SELECT node contains aggregate functions.
     *
     * @param SelectNode|null $selectNode
     * @return bool
     */
    public function hasAggregateSelect(?SelectNode $selectNode): bool
    {
        if ($selectNode === null) {
            return false;
        }

        foreach ($selectNode->getFields() as $field) {
            if ($field instanceof AggregateFunctionNode) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if SELECT node contains plain (non-aggregate) fields.
     *
     * @param SelectNode|null $selectNode
     * @return bool
     */
    public function hasPlainSelectFields(?SelectNode $selectNode): bool
    {
        if ($selectNode === null) {
            return false;
        }

        foreach ($selectNode->getFields() as $field) {
            if (is_string($field) && $field !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * Build SELECT field descriptors for aggregation processing.
     *
     * Descriptors describe what fields to include in result and how to compute them.
     *
     * @param Query $query
     * @param string[] $groupFields GROUP BY fields
     * @return array<int,array<string,mixed>> Array of field descriptors
     * @throws DataStoreException
     */
    public function buildSelectDescriptors(Query $query, array $groupFields): array
    {
        $selectNode = $query->getSelect();

        // If no SELECT specified with GROUP BY, return all group fields
        if ($selectNode === null) {
            return array_map(static fn(string $field): array => [
                'type' => 'group',
                'field' => $field,
                'label' => $field,
            ], $groupFields);
        }

        $descriptors = [];

        foreach ($selectNode->getFields() as $field) {
            // Handle aggregate functions: count(), sum(), avg(), min(), max()
            if ($field instanceof AggregateFunctionNode) {
                $function = strtolower((string) $field->getFunction());
                if (!in_array($function, ['count', 'max', 'min', 'sum', 'avg'], true)) {
                    throw new DataStoreException('Unsupported aggregate function: ' . $field->getFunction());
                }

                $descriptors[] = [
                    'type' => 'metric',
                    'function' => $function,
                    'field' => (string) $field->getField(),
                    'label' => $field->__toString(), // e.g., "count(status)"
                ];
                continue;
            }

            // Handle plain field names (non-aggregate)
            if (!is_string($field) || $field === '') {
                continue;
            }

            // If field is in GROUP BY, include it as grouping dimension
            if ($groupFields !== [] && in_array($field, $groupFields, true)) {
                $descriptors[] = [
                    'type' => 'group',
                    'field' => $field,
                    'label' => $field,
                ];
                continue;
            }

            // If field is NOT in GROUP BY but we have grouping, convert to count(field)
            // This handles invalid SQL-like: SELECT name FROM ... GROUP BY category
            // Converts 'name' to 'count(name)' to make it valid aggregation
            if ($groupFields !== []) {
                $descriptors[] = [
                    'type' => 'metric',
                    'function' => 'count',
                    'field' => $field,
                    'label' => 'count(' . $field . ')',
                ];
            }
        }

        return $descriptors;
    }

    /**
     * Attach unique metric aliases to select descriptors.
     *
     * Elasticsearch requires unique names for aggregations,
     * so we assign metric_0, metric_1, etc. to each metric descriptor.
     *
     * @param array<int,array<string,mixed>> $selectDescriptors
     * @return array<int,array<string,mixed>>
     */
    public function attachMetricAliases(array $selectDescriptors): array
    {
        $index = 0;
        foreach ($selectDescriptors as $key => $descriptor) {
            if (($descriptor['type'] ?? null) !== 'metric') {
                continue;
            }

            $selectDescriptors[$key]['alias'] = 'metric_' . $index;
            $index++;
        }

        return $selectDescriptors;
    }

    /**
     * Build Elasticsearch metric aggregations from select descriptors.
     *
     * @param array<int,array<string,mixed>> $selectDescriptors
     * @return array<string,array> Elasticsearch aggregations object
     * @throws DataStoreException
     */
    public function buildMetricAggregations(array $selectDescriptors): array
    {
        $aggregations = [];

        foreach ($selectDescriptors as $descriptor) {
            if (($descriptor['type'] ?? null) !== 'metric') {
                continue;
            }

            $alias = $descriptor['alias'] ?? null;
            if (!is_string($alias) || $alias === '') {
                continue;
            }

            $field = (string) ($descriptor['field'] ?? '');
            $function = (string) ($descriptor['function'] ?? '');

            // Build Elasticsearch aggregation based on function type
            $aggregations[$alias] = match ($function) {
                // count() uses filter + exists (or match_all for identifier)
                'count' => $field === $this->identifier
                    ? ['filter' => ['match_all' => (object) []]] // count(*) equivalent
                    : ['filter' => ['exists' => ['field' => $field]]], // count non-null values
                'max' => ['max' => ['field' => $field]],
                'min' => ['min' => ['field' => $field]],
                'sum' => ['sum' => ['field' => $field]],
                'avg' => ['avg' => ['field' => $field]],
                default => throw new DataStoreException('Unsupported aggregate function: ' . $function),
            };
        }

        return $aggregations;
    }

    /**
     * Build composite aggregation sources for GROUP BY.
     *
     * @param string[] $groupFields GROUP BY field names
     * @param SortNode|null $sortNode RQL sort node (for ordering groups)
     * @return array{sources: array<int,array<string,array>>, byField: array<string,string>}
     * @throws DataStoreException
     */
    public function buildGroupSources(array $groupFields, ?SortNode $sortNode): array
    {
        // Extract sort directions for group fields
        $directions = [];
        if ($sortNode !== null) {
            foreach ($sortNode->getFields() as $field => $direction) {
                if (!is_string($field) || $field === '') {
                    continue;
                }

                $direction = (int) $direction;
                if ($direction !== SortNode::SORT_ASC && $direction !== SortNode::SORT_DESC) {
                    throw new DataStoreException('Invalid sort direction: ' . $direction);
                }

                $directions[$field] = $direction === SortNode::SORT_ASC ? 'asc' : 'desc';
            }
        }

        $sources = [];
        $byField = [];

        foreach ($groupFields as $index => $field) {
            $sourceName = 'group_' . $index;
            $byField[$field] = $sourceName;
            $order = $directions[$field] ?? 'asc';

            $sources[] = [
                $sourceName => [
                    'terms' => [
                        'field' => $field,
                        'order' => $order,
                        'missing_bucket' => true,
                    ],
                ],
            ];
        }

        return [
            'sources' => $sources,
            'byField' => $byField,
        ];
    }
}
