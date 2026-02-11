<?php

declare(strict_types=1);

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Elasticsearch;

use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use rollun\datastore\DataStore\DataStoreAbstract;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\Rql\RqlQuery;
use Xiag\Rql\Parser\Node\LimitNode;
use Xiag\Rql\Parser\Node\SelectNode;
use Xiag\Rql\Parser\Query;

/**
 * Builds and executes Elasticsearch queries from RQL.
 *
 * This class is the central coordinator for query building, analogous to SqlQueryBuilder.
 *
 * Responsibilities:
 * - Building complete Elasticsearch request body
 * - Coordinating all sub-builders (Sort, Aggregation, Condition)
 * - Handling limit/offset with search_after pagination
 * - Processing SELECT fields
 * - Determining query type (regular/aggregation/in-memory)
 * - Executing batch queries for pagination
 */
class ElasticsearchQueryBuilder
{
    private const SEARCH_BATCH_SIZE = 500;
    private const DEFAULT_QUERY_LIMIT = 10000;

    private readonly RqlToElasticsearchDslAdapter $conditionBuilder;
    private readonly ElasticsearchSortBuilder $sortBuilder;
    private readonly ElasticsearchAggregationBuilder $aggregationBuilder;
    private readonly ElasticsearchResultNormalizer $resultNormalizer;

    public function __construct(
        private readonly Client $client,
        private readonly string $index,
        private readonly string $identifier = 'id',
        private readonly LoggerInterface $logger = new NullLogger()
    ) {
        $this->conditionBuilder = new RqlToElasticsearchDslAdapter(
            $this->client,
            $this->index,
            $this->identifier,
            $this->logger
        );
        $this->sortBuilder = new ElasticsearchSortBuilder($this->identifier);
        $this->aggregationBuilder = new ElasticsearchAggregationBuilder();
        $this->resultNormalizer = new ElasticsearchResultNormalizer($this->identifier);
    }

    /**
     * Execute RQL query and return results.
     *
     * @param Query $query
     * @return array
     * @throws DataStoreException
     */
    public function query(Query $query): array
    {
        if ($this->aggregationBuilder->shouldUseNativeAggregations($query)) {
            return $this->queryWithNativeAggregations($query);
        }

        if ($this->shouldProcessSelectInMemory($query)) {
            return $this->queryWithInMemorySelect($query);
        }

        return $this->queryRegular($query);
    }

    /**
     * Execute regular (non-aggregation) query with pagination.
     *
     * @param Query $query
     * @return array
     * @throws DataStoreException
     */
    private function queryRegular(Query $query): array
    {
        $limitNode = $query->getLimit();
        $limit = $this->resolveLimit($limitNode);
        $offset = $limitNode ? max(0, (int) $limitNode->getOffset()) : 0;

        if ($limit < 0) {
            throw new DataStoreException('Query limit must be greater or equal to zero.');
        }

        if ($limit === 0) {
            return [];
        }

        $selectFields = $this->extractSelectFields($query->getSelect());
        $sort = $this->sortBuilder->buildSortWithTieBreaker($query->getSort());

        $baseBody = [
            'query' => $this->conditionBuilder->convert($query->getQuery()),
            'sort' => $sort,
        ];

        if ($selectFields !== []) {
            $baseBody['_source'] = $selectFields;
        }

        $result = [];
        $skipped = 0;
        $remaining = $limit === DataStoreAbstract::LIMIT_INFINITY ? null : $limit;
        $searchAfter = null;

        while (true) {
            $batchSize = $this->calculateBatchSize($remaining, $offset, $skipped);

            if ($batchSize === 0) {
                break;
            }

            $body = $baseBody;
            $body['size'] = $batchSize;

            if ($searchAfter !== null) {
                $body['search_after'] = $searchAfter;
            }

            try {
                $response = $this->client->search([
                    'index' => $this->index,
                    'body' => $body,
                ]);
            } catch (Missing404Exception) {
                $this->logger->info('ElasticsearchQueryBuilder query: index not found', [
                    'index' => $this->index,
                ]);
                return [];
            }

            if (!is_array($response)) {
                break;
            }

            $hits = $response['hits']['hits'] ?? null;

            if (!is_array($hits) || $hits === []) {
                break;
            }

            foreach ($hits as $hit) {
                if (!is_array($hit)) {
                    continue;
                }

                if ($skipped < $offset) {
                    $skipped++;
                    continue;
                }

                $result[] = $this->resultNormalizer->normalizeSearchHit($hit, $selectFields);

                if ($remaining !== null) {
                    $remaining--;
                    if ($remaining === 0) {
                        break 2;
                    }
                }
            }

            $lastHit = end($hits);
            $lastSort = is_array($lastHit) ? ($lastHit['sort'] ?? null) : null;

            if (!is_array($lastSort)) {
                break;
            }

            $searchAfter = $lastSort;
        }

        return $result;
    }

    /**
     * Execute query with native Elasticsearch aggregations.
     *
     * @param Query $query
     * @return array
     * @throws DataStoreException
     */
    private function queryWithNativeAggregations(Query $query): array
    {
        $limitNode = $query->getLimit();
        $limit = $this->resolveLimit($limitNode);
        $offset = $limitNode ? max(0, (int) $limitNode->getOffset()) : 0;

        if ($limit < 0) {
            throw new DataStoreException('Query limit must be greater or equal to zero.');
        }

        if ($limit === 0) {
            return [];
        }

        $groupFields = $this->aggregationBuilder->extractGroupByFields($query);

        if ($groupFields !== []) {
            return $this->queryGroupedAggregations($query, $groupFields, $limit, $offset);
        }

        return $this->queryMetricAggregations($query, $limit, $offset);
    }

    /**
     * Execute grouped aggregation query (GROUP BY).
     *
     * @param Query $query
     * @param string[] $groupFields
     * @param int $limit
     * @param int $offset
     * @return array
     * @throws DataStoreException
     */
    private function queryGroupedAggregations(
        Query $query,
        array $groupFields,
        int $limit,
        int $offset
    ): array {
        $selectDescriptors = $this->aggregationBuilder->attachMetricAliases(
            $this->aggregationBuilder->buildSelectDescriptors($query, $groupFields)
        );
        $metricAggregations = $this->aggregationBuilder->buildMetricAggregations($selectDescriptors);
        $groupSources = $this->aggregationBuilder->buildGroupSources($groupFields, $query->getSort());

        $result = [];
        $remaining = $limit === DataStoreAbstract::LIMIT_INFINITY ? null : $limit;
        $skipped = 0;
        $afterKey = null;

        while (true) {
            $composite = [
                'size' => self::SEARCH_BATCH_SIZE,
                'sources' => $groupSources['sources'],
            ];

            if ($afterKey !== null) {
                $composite['after'] = $afterKey;
            }

            $body = [
                'size' => 0,
                'query' => $this->conditionBuilder->convert($query->getQuery()),
                'aggs' => [
                    'groupby' => [
                        'composite' => $composite,
                        'aggs' => $metricAggregations,
                    ],
                ],
            ];

            try {
                $response = $this->client->search([
                    'index' => $this->index,
                    'body' => $body,
                ]);
            } catch (Missing404Exception) {
                $this->logger->info('ElasticsearchQueryBuilder query: index not found', [
                    'index' => $this->index,
                ]);
                return [];
            }

            if (!is_array($response)) {
                break;
            }

            $groupBy = $response['aggregations']['groupby'] ?? null;
            $buckets = is_array($groupBy) ? ($groupBy['buckets'] ?? null) : null;

            if (!is_array($buckets) || $buckets === []) {
                break;
            }

            foreach ($buckets as $bucket) {
                if (!is_array($bucket)) {
                    continue;
                }

                if ($skipped < $offset) {
                    $skipped++;
                    continue;
                }

                $result[] = $this->resultNormalizer->hydrateGroupedResultRow(
                    $bucket,
                    $selectDescriptors,
                    $groupSources['byField']
                );

                if ($remaining !== null) {
                    $remaining--;
                    if ($remaining === 0) {
                        break 2;
                    }
                }
            }

            $afterKey = is_array($groupBy) ? ($groupBy['after_key'] ?? null) : null;

            if (!is_array($afterKey) || $afterKey === []) {
                break;
            }
        }

        return $this->resultNormalizer->normalizeResultSetShape($result);
    }

    /**
     * Execute metric aggregation query (without GROUP BY).
     *
     * @param Query $query
     * @param int $limit
     * @param int $offset
     * @return array
     * @throws DataStoreException
     */
    private function queryMetricAggregations(Query $query, int $limit, int $offset): array
    {
        if ($offset > 0) {
            return [];
        }

        if ($limit !== DataStoreAbstract::LIMIT_INFINITY && $limit < 1) {
            return [];
        }

        $selectDescriptors = $this->aggregationBuilder->attachMetricAliases(
            $this->aggregationBuilder->buildSelectDescriptors($query, [])
        );
        $metricAggregations = $this->aggregationBuilder->buildMetricAggregations($selectDescriptors);

        if ($metricAggregations === []) {
            return [];
        }

        $body = [
            'size' => 0,
            'query' => $this->conditionBuilder->convert($query->getQuery()),
            'aggs' => $metricAggregations,
        ];

        try {
            $response = $this->client->search([
                'index' => $this->index,
                'body' => $body,
            ]);
        } catch (Missing404Exception) {
            $this->logger->info('ElasticsearchQueryBuilder query: index not found', [
                'index' => $this->index,
            ]);
            return [];
        }

        if (!is_array($response)) {
            return [];
        }

        $aggregations = $response['aggregations'] ?? null;
        if (!is_array($aggregations)) {
            return [];
        }

        $row = $this->resultNormalizer->normalizeMetricAggregationResult($aggregations, $selectDescriptors);

        if ($row === []) {
            return [];
        }

        return [$row];
    }

    /**
     * Check if query should process SELECT in memory.
     *
     * This happens when query has both aggregate and plain fields in SELECT,
     * which Elasticsearch doesn't support directly.
     *
     * @param Query $query
     * @return bool
     */
    private function shouldProcessSelectInMemory(Query $query): bool
    {
        $selectNode = $query->getSelect();

        if (!$this->aggregationBuilder->hasAggregateSelect($selectNode)) {
            return false;
        }

        if ($query instanceof RqlQuery && $query->getGroupBy() !== null) {
            return false;
        }

        return $this->aggregationBuilder->hasPlainSelectFields($selectNode);
    }

    /**
     * Execute query with in-memory processing of SELECT.
     *
     * @param Query $query
     * @return array
     * @throws DataStoreException
     */
    private function queryWithInMemorySelect(Query $query): array
    {
        $limitNode = $query->getLimit();
        $limit = $this->resolveLimit($limitNode);
        $offset = $limitNode ? max(0, (int) $limitNode->getOffset()) : 0;

        if ($limit < 0) {
            throw new DataStoreException('Query limit must be greater or equal to zero.');
        }

        if ($limit === 0) {
            return [];
        }

        // Fetch all data without SELECT
        $rawQuery = new Query();
        if ($query->getQuery() !== null) {
            $rawQuery->setQuery($query->getQuery());
        }
        if ($query->getSort() !== null) {
            $rawQuery->setSort($query->getSort());
        }
        $rawQuery->setLimit(new LimitNode(DataStoreAbstract::LIMIT_INFINITY, 0));

        $data = $this->queryRegular($rawQuery);

        // Process SELECT in memory (this would need DataStore's querySelect method)
        // For now, we just apply limit/offset
        $result = array_slice($data, $offset, $limit === DataStoreAbstract::LIMIT_INFINITY ? null : $limit);

        return $this->resultNormalizer->normalizeResultSetShape($result);
    }

    /**
     * Extract SELECT field names from SelectNode.
     *
     * @param SelectNode|null $selectNode
     * @return string[]
     */
    private function extractSelectFields(?SelectNode $selectNode): array
    {
        if ($selectNode === null) {
            return [];
        }

        $fields = [];

        foreach ($selectNode->getFields() as $key => $value) {
            $field = is_int($key) ? $value : $key;

            if (!is_string($field) || $field === '') {
                continue;
            }

            $fields[] = $field;
        }

        return array_values(array_unique($fields));
    }

    /**
     * Calculate batch size for pagination.
     *
     * @param int|null $remaining Remaining records to fetch (null = unlimited)
     * @param int $offset Total offset
     * @param int $skipped Already skipped records
     * @return int Batch size for next request
     */
    private function calculateBatchSize(?int $remaining, int $offset, int $skipped): int
    {
        if ($remaining === 0) {
            return 0;
        }

        $needToSkip = max(0, $offset - $skipped);
        $needToTake = $remaining ?? self::SEARCH_BATCH_SIZE;

        return max(1, min(self::SEARCH_BATCH_SIZE, $needToSkip + $needToTake));
    }

    /**
     * Resolve limit from LimitNode or use default.
     *
     * @param LimitNode|null $limitNode
     * @return int
     */
    private function resolveLimit(?LimitNode $limitNode): int
    {
        if ($limitNode === null) {
            return self::DEFAULT_QUERY_LIMIT;
        }

        return (int) $limitNode->getLimit();
    }
}
