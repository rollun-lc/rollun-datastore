<?php

declare(strict_types=1);

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\unit\DataStore\Elasticsearch;

use PHPUnit\Framework\TestCase;
use rollun\datastore\DataStore\Elasticsearch\ElasticsearchResultNormalizer;

class ElasticsearchResultNormalizerTest extends TestCase
{
    // ========================================
    // normalizeSearchHit() Tests
    // ========================================

    public function testNormalizeSearchHitExtractsSourceAndInjectsId(): void
    {
        $normalizer = new ElasticsearchResultNormalizer('id');
        $hit = [
            '_id' => 'doc123',
            '_source' => [
                'name' => 'Product A',
                'price' => 100,
            ],
        ];

        $result = $normalizer->normalizeSearchHit($hit);

        $expected = [
            'name' => 'Product A',
            'price' => 100,
            'id' => 'doc123',
        ];

        $this->assertSame($expected, $result);
    }

    public function testNormalizeSearchHitDoesNotOverrideExistingIdentifier(): void
    {
        $normalizer = new ElasticsearchResultNormalizer('id');
        $hit = [
            '_id' => 'es_id_123',
            '_source' => [
                'id' => 'custom_id_456',
                'name' => 'Product B',
            ],
        ];

        $result = $normalizer->normalizeSearchHit($hit);

        $expected = [
            'id' => 'custom_id_456',
            'name' => 'Product B',
        ];

        // Should keep the id from _source, not inject _id
        $this->assertSame($expected, $result);
    }

    public function testNormalizeSearchHitWithSelectFields(): void
    {
        $normalizer = new ElasticsearchResultNormalizer('id');
        $hit = [
            '_id' => 'doc123',
            '_source' => [
                'name' => 'Product A',
                'price' => 100,
                'description' => 'Long text',
            ],
        ];

        $result = $normalizer->normalizeSearchHit($hit, ['name', 'price']);

        $expected = [
            'name' => 'Product A',
            'price' => 100,
        ];

        $this->assertSame($expected, $result);
    }

    public function testNormalizeSearchHitWithMissingSelectFields(): void
    {
        $normalizer = new ElasticsearchResultNormalizer('id');
        $hit = [
            '_id' => 'doc123',
            '_source' => [
                'name' => 'Product A',
            ],
        ];

        $result = $normalizer->normalizeSearchHit($hit, ['name', 'price', 'category']);

        $expected = [
            'name' => 'Product A',
            'price' => null,
            'category' => null,
        ];

        $this->assertSame($expected, $result);
    }

    /**
     * Edge case: _source is not an array.
     */
    public function testNormalizeSearchHitWithNonArraySource(): void
    {
        $normalizer = new ElasticsearchResultNormalizer('id');
        $hit = [
            '_id' => 'doc123',
            '_source' => 'invalid',
        ];

        $result = $normalizer->normalizeSearchHit($hit);

        $expected = [
            'id' => 'doc123',
        ];

        $this->assertSame($expected, $result);
    }

    /**
     * Edge case: _source is missing entirely.
     */
    public function testNormalizeSearchHitWithMissingSource(): void
    {
        $normalizer = new ElasticsearchResultNormalizer('id');
        $hit = [
            '_id' => 'doc123',
        ];

        $result = $normalizer->normalizeSearchHit($hit);

        $expected = [
            'id' => 'doc123',
        ];

        $this->assertSame($expected, $result);
    }

    /**
     * Edge case: _id is numeric (int).
     */
    public function testNormalizeSearchHitWithNumericId(): void
    {
        $normalizer = new ElasticsearchResultNormalizer('id');
        $hit = [
            '_id' => 12345,
            '_source' => [
                'name' => 'Product A',
            ],
        ];

        $result = $normalizer->normalizeSearchHit($hit);

        $expected = [
            'name' => 'Product A',
            'id' => 12345,
        ];

        $this->assertSame($expected, $result);
    }

    /**
     * Edge case: Empty selectFields array (should return all fields).
     */
    public function testNormalizeSearchHitWithEmptySelectFields(): void
    {
        $normalizer = new ElasticsearchResultNormalizer('id');
        $hit = [
            '_id' => 'doc123',
            '_source' => [
                'name' => 'Product A',
                'price' => 100,
            ],
        ];

        $result = $normalizer->normalizeSearchHit($hit, []);

        $expected = [
            'name' => 'Product A',
            'price' => 100,
            'id' => 'doc123',
        ];

        $this->assertSame($expected, $result);
    }

    // ========================================
    // hydrateGroupedResultRow() Tests
    // ========================================

    public function testHydrateGroupedResultRowWithGroupAndMetric(): void
    {
        $normalizer = new ElasticsearchResultNormalizer('id');
        $bucket = [
            'key' => [
                'group_0' => 'CategoryA',
                'group_1' => 'BrandX',
            ],
            'doc_count' => 150,
            'metric_0' => [
                'value' => 1500,
            ],
        ];

        $selectDescriptors = [
            ['type' => 'group', 'field' => 'category', 'label' => 'category'],
            ['type' => 'group', 'field' => 'brand', 'label' => 'brand'],
            ['type' => 'metric', 'alias' => 'metric_0', 'function' => 'sum', 'label' => 'total_price'],
        ];

        $groupFieldMap = [
            'category' => 'group_0',
            'brand' => 'group_1',
        ];

        $result = $normalizer->hydrateGroupedResultRow($bucket, $selectDescriptors, $groupFieldMap);

        $expected = [
            'category' => 'CategoryA',
            'brand' => 'BrandX',
            'total_price' => 1500,
        ];

        $this->assertSame($expected, $result);
    }

    /**
     * Edge case: bucket['key'] is not an array.
     */
    public function testHydrateGroupedResultRowWithNonArrayKey(): void
    {
        $normalizer = new ElasticsearchResultNormalizer('id');
        $bucket = [
            'key' => 'invalid',
            'metric_0' => [
                'value' => 100,
            ],
        ];

        $selectDescriptors = [
            ['type' => 'group', 'field' => 'category', 'label' => 'category'],
            ['type' => 'metric', 'alias' => 'metric_0', 'function' => 'sum', 'label' => 'total'],
        ];

        $groupFieldMap = ['category' => 'group_0'];

        $result = $normalizer->hydrateGroupedResultRow($bucket, $selectDescriptors, $groupFieldMap);

        $expected = [
            'category' => null,
            'total' => 100,
        ];

        $this->assertSame($expected, $result);
    }

    /**
     * Edge case: Empty label should be skipped.
     */
    public function testHydrateGroupedResultRowSkipsEmptyLabel(): void
    {
        $normalizer = new ElasticsearchResultNormalizer('id');
        $bucket = [
            'key' => ['group_0' => 'ValueA'],
            'metric_0' => ['value' => 100],
        ];

        $selectDescriptors = [
            ['type' => 'group', 'field' => 'category', 'label' => ''], // Empty label
            ['type' => 'metric', 'alias' => 'metric_0', 'function' => 'sum', 'label' => 'total'],
        ];

        $groupFieldMap = ['category' => 'group_0'];

        $result = $normalizer->hydrateGroupedResultRow($bucket, $selectDescriptors, $groupFieldMap);

        $expected = [
            'total' => 100,
        ];

        $this->assertSame($expected, $result);
    }

    /**
     * Edge case: Missing key in groupFieldMap.
     */
    public function testHydrateGroupedResultRowWithMissingGroupFieldMap(): void
    {
        $normalizer = new ElasticsearchResultNormalizer('id');
        $bucket = [
            'key' => ['group_0' => 'ValueA'],
        ];

        $selectDescriptors = [
            ['type' => 'group', 'field' => 'category', 'label' => 'category'],
        ];

        $groupFieldMap = []; // Missing mapping

        $result = $normalizer->hydrateGroupedResultRow($bucket, $selectDescriptors, $groupFieldMap);

        $expected = [
            'category' => null,
        ];

        $this->assertSame($expected, $result);
    }

    // ========================================
    // extractMetricValue() Tests
    // ========================================

    public function testExtractMetricValueForCount(): void
    {
        $normalizer = new ElasticsearchResultNormalizer('id');
        $aggregationContainer = [
            'metric_0' => [
                'doc_count' => 250,
            ],
        ];

        $descriptor = [
            'alias' => 'metric_0',
            'function' => 'count',
        ];

        $result = $normalizer->extractMetricValue($aggregationContainer, $descriptor);

        $this->assertSame(250, $result);
    }

    public function testExtractMetricValueForSum(): void
    {
        $normalizer = new ElasticsearchResultNormalizer('id');
        $aggregationContainer = [
            'metric_0' => [
                'value' => 1500.5,
            ],
        ];

        $descriptor = [
            'alias' => 'metric_0',
            'function' => 'sum',
        ];

        $result = $normalizer->extractMetricValue($aggregationContainer, $descriptor);

        $this->assertSame(1500.5, $result);
    }

    /**
     * Edge case: Missing alias in descriptor.
     */
    public function testExtractMetricValueWithMissingAlias(): void
    {
        $normalizer = new ElasticsearchResultNormalizer('id');
        $aggregationContainer = [
            'metric_0' => ['value' => 100],
        ];

        $descriptor = [
            'function' => 'sum',
        ];

        $result = $normalizer->extractMetricValue($aggregationContainer, $descriptor);

        $this->assertNull($result);
    }

    /**
     * Edge case: Empty string alias.
     */
    public function testExtractMetricValueWithEmptyAlias(): void
    {
        $normalizer = new ElasticsearchResultNormalizer('id');
        $aggregationContainer = [
            'metric_0' => ['value' => 100],
        ];

        $descriptor = [
            'alias' => '',
            'function' => 'sum',
        ];

        $result = $normalizer->extractMetricValue($aggregationContainer, $descriptor);

        $this->assertNull($result);
    }

    /**
     * Edge case: Aggregation is not an array.
     */
    public function testExtractMetricValueWithNonArrayAggregation(): void
    {
        $normalizer = new ElasticsearchResultNormalizer('id');
        $aggregationContainer = [
            'metric_0' => 'invalid',
        ];

        $descriptor = [
            'alias' => 'metric_0',
            'function' => 'sum',
        ];

        $result = $normalizer->extractMetricValue($aggregationContainer, $descriptor);

        $this->assertNull($result);
    }

    /**
     * Edge case: Missing doc_count for count function.
     */
    public function testExtractMetricValueCountWithMissingDocCount(): void
    {
        $normalizer = new ElasticsearchResultNormalizer('id');
        $aggregationContainer = [
            'metric_0' => [],
        ];

        $descriptor = [
            'alias' => 'metric_0',
            'function' => 'count',
        ];

        $result = $normalizer->extractMetricValue($aggregationContainer, $descriptor);

        $this->assertSame(0, $result);
    }

    /**
     * Edge case: Missing value for non-count function.
     */
    public function testExtractMetricValueWithMissingValue(): void
    {
        $normalizer = new ElasticsearchResultNormalizer('id');
        $aggregationContainer = [
            'metric_0' => [],
        ];

        $descriptor = [
            'alias' => 'metric_0',
            'function' => 'sum',
        ];

        $result = $normalizer->extractMetricValue($aggregationContainer, $descriptor);

        $this->assertNull($result);
    }

    // ========================================
    // normalizeResultSetShape() Tests
    // ========================================

    public function testNormalizeResultSetShapeEnsuresAllFieldsPresent(): void
    {
        $normalizer = new ElasticsearchResultNormalizer('id');
        $result = [
            ['name' => 'A', 'price' => 100],
            ['name' => 'B', 'category' => 'X'],
            ['price' => 200, 'category' => 'Y'],
        ];

        $normalized = $normalizer->normalizeResultSetShape($result);

        $expected = [
            ['name' => 'A', 'price' => 100, 'category' => null],
            ['name' => 'B', 'category' => 'X', 'price' => null],
            ['price' => 200, 'category' => 'Y', 'name' => null],
        ];

        $this->assertSame($expected, $normalized);
    }

    /**
     * Edge case: Empty result array.
     */
    public function testNormalizeResultSetShapeWithEmptyArray(): void
    {
        $normalizer = new ElasticsearchResultNormalizer('id');
        $result = [];

        $normalized = $normalizer->normalizeResultSetShape($result);

        $this->assertSame([], $normalized);
    }

    /**
     * Edge case: Result contains non-array elements.
     */
    public function testNormalizeResultSetShapeSkipsNonArrayElements(): void
    {
        $normalizer = new ElasticsearchResultNormalizer('id');
        $result = [
            ['name' => 'A'],
            'invalid',
            ['name' => 'B', 'price' => 100],
            null,
        ];

        $normalized = $normalizer->normalizeResultSetShape($result);

        $expected = [
            ['name' => 'A', 'price' => null],
            'invalid',
            ['name' => 'B', 'price' => 100],
            null,
        ];

        $this->assertSame($expected, $normalized);
    }

    /**
     * Edge case: Single row in result.
     */
    public function testNormalizeResultSetShapeWithSingleRow(): void
    {
        $normalizer = new ElasticsearchResultNormalizer('id');
        $result = [
            ['name' => 'A', 'price' => 100],
        ];

        $normalized = $normalizer->normalizeResultSetShape($result);

        $expected = [
            ['name' => 'A', 'price' => 100],
        ];

        $this->assertSame($expected, $normalized);
    }

    // ========================================
    // normalizeMetricAggregationResult() Tests
    // ========================================

    public function testNormalizeMetricAggregationResult(): void
    {
        $normalizer = new ElasticsearchResultNormalizer('id');
        $aggregations = [
            'metric_0' => ['value' => 1500],
            'metric_1' => ['doc_count' => 250],
        ];

        $selectDescriptors = [
            ['type' => 'metric', 'alias' => 'metric_0', 'function' => 'sum', 'label' => 'total_price'],
            ['type' => 'metric', 'alias' => 'metric_1', 'function' => 'count', 'label' => 'count'],
        ];

        $result = $normalizer->normalizeMetricAggregationResult($aggregations, $selectDescriptors);

        $expected = [
            'total_price' => 1500,
            'count' => 250,
        ];

        $this->assertSame($expected, $result);
    }

    /**
     * Edge case: Descriptor with non-metric type should be skipped.
     */
    public function testNormalizeMetricAggregationResultSkipsNonMetricDescriptors(): void
    {
        $normalizer = new ElasticsearchResultNormalizer('id');
        $aggregations = [
            'metric_0' => ['value' => 1500],
        ];

        $selectDescriptors = [
            ['type' => 'group', 'field' => 'category', 'label' => 'category'],
            ['type' => 'metric', 'alias' => 'metric_0', 'function' => 'sum', 'label' => 'total'],
        ];

        $result = $normalizer->normalizeMetricAggregationResult($aggregations, $selectDescriptors);

        $expected = [
            'total' => 1500,
        ];

        $this->assertSame($expected, $result);
    }

    /**
     * Edge case: Empty label should be skipped.
     */
    public function testNormalizeMetricAggregationResultSkipsEmptyLabel(): void
    {
        $normalizer = new ElasticsearchResultNormalizer('id');
        $aggregations = [
            'metric_0' => ['value' => 1500],
        ];

        $selectDescriptors = [
            ['type' => 'metric', 'alias' => 'metric_0', 'function' => 'sum', 'label' => ''],
        ];

        $result = $normalizer->normalizeMetricAggregationResult($aggregations, $selectDescriptors);

        $expected = [];

        $this->assertSame($expected, $result);
    }
}
