<?php

declare(strict_types=1);

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Elasticsearch;

/**
 * Normalizes Elasticsearch search results and aggregation responses.
 *
 * This class is responsible for:
 * - Normalizing search hits to standard record format
 * - Extracting _source and injecting identifier field
 * - Hydrating grouped aggregation results
 * - Extracting metric values from aggregations
 * - Normalizing result set shape (ensuring all rows have same fields)
 */
final class ElasticsearchResultNormalizer
{
    public function __construct(
        private readonly string $identifier = 'id'
    ) {
    }

    /**
     * Normalize a single search hit to standard record format.
     *
     * @param array $hit Elasticsearch hit from search response
     * @param string[] $selectFields Fields to include in result (empty = all fields)
     * @return array Normalized record
     */
    public function normalizeSearchHit(array $hit, array $selectFields = []): array
    {
        $record = $hit['_source'] ?? [];

        if (!is_array($record)) {
            $record = [];
        }

        // Inject identifier from _id if not present in _source
        if (!array_key_exists($this->identifier, $record) && array_key_exists('_id', $hit)) {
            $record[$this->identifier] = $hit['_id'];
        }

        // Apply field selection if specified
        if ($selectFields === []) {
            return $record;
        }

        $selected = [];
        foreach ($selectFields as $field) {
            $selected[$field] = $record[$field] ?? null;
        }

        return $selected;
    }

    /**
     * Hydrate a grouped aggregation result row from bucket data.
     *
     * @param array $bucket Elasticsearch aggregation bucket
     * @param array<int,array<string,mixed>> $selectDescriptors Field descriptors
     * @param array<string,string> $groupFieldMap Mapping of field names to source names
     * @return array<string,mixed> Hydrated result row
     */
    public function hydrateGroupedResultRow(
        array $bucket,
        array $selectDescriptors,
        array $groupFieldMap
    ): array {
        $key = $bucket['key'] ?? [];
        if (!is_array($key)) {
            $key = [];
        }

        $row = [];

        foreach ($selectDescriptors as $descriptor) {
            $type = $descriptor['type'] ?? null;
            $label = (string) ($descriptor['label'] ?? '');

            if ($label === '') {
                continue;
            }

            if ($type === 'group') {
                $field = (string) ($descriptor['field'] ?? '');
                $sourceName = $groupFieldMap[$field] ?? null;
                $row[$label] = is_string($sourceName) ? ($key[$sourceName] ?? null) : null;
                continue;
            }

            if ($type === 'metric') {
                $row[$label] = $this->extractMetricValue($bucket, $descriptor);
            }
        }

        return $row;
    }

    /**
     * Extract metric value from aggregation container.
     *
     * @param array<string,mixed> $aggregationContainer Bucket or aggregations object
     * @param array<string,mixed> $descriptor Metric descriptor with alias and function
     * @return mixed Extracted metric value
     */
    public function extractMetricValue(array $aggregationContainer, array $descriptor): mixed
    {
        $alias = $descriptor['alias'] ?? null;
        if (!is_string($alias) || $alias === '') {
            return null;
        }

        $aggregation = $aggregationContainer[$alias] ?? null;
        if (!is_array($aggregation)) {
            return null;
        }

        $function = (string) ($descriptor['function'] ?? '');
        if ($function === 'count') {
            return (int) ($aggregation['doc_count'] ?? 0);
        }

        return $aggregation['value'] ?? null;
    }

    /**
     * Normalize result set shape by ensuring all rows have the same fields.
     *
     * This is necessary because aggregation results may have missing fields
     * in some rows (e.g., when grouping by nullable fields).
     *
     * @param array<int,array<string,mixed>> $result
     * @return array<int,array<string,mixed>>
     */
    public function normalizeResultSetShape(array $result): array
    {
        $allFields = [];

        // First pass: collect all unique field names across all rows
        // This is needed because aggregation results may have different fields per row
        // (e.g., when grouping by nullable fields or when some metrics are missing)
        foreach ($result as &$item) {
            if (!is_array($item)) {
                continue;
            }

            $keys = array_keys($item);
            $diff = array_diff($keys, $allFields);
            $allFields = array_merge($allFields, $diff);
        }

        // Second pass: ensure each row has all fields (with null for missing ones)
        // This ensures consistent structure for all rows in the result set
        foreach ($result as &$item) {
            if (!is_array($item)) {
                continue;
            }

            $diff = array_diff($allFields, array_keys($item));

            foreach ($diff as $field) {
                $item[$field] = null; // Fill missing fields with null
            }
        }

        return $result;
    }

    /**
     * Normalize metric aggregation result to single-row format.
     *
     * @param array $aggregations Elasticsearch aggregations response
     * @param array<int,array<string,mixed>> $selectDescriptors Metric descriptors
     * @return array Single-row result with metric values
     */
    public function normalizeMetricAggregationResult(
        array $aggregations,
        array $selectDescriptors
    ): array {
        $row = [];

        foreach ($selectDescriptors as $descriptor) {
            if (($descriptor['type'] ?? null) !== 'metric') {
                continue;
            }

            $label = $descriptor['label'] ?? '';
            if ($label === '') {
                continue;
            }

            $row[$label] = $this->extractMetricValue($aggregations, $descriptor);
        }

        return $row;
    }
}
