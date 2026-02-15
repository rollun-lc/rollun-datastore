<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\functional\DataStore\Elasticsearch;

use PHPUnit\Framework\TestCase;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\Elasticsearch\ElasticsearchAggregationBuilder;
use rollun\datastore\Rql\Node\AggregateFunctionNode;
use rollun\datastore\Rql\RqlQuery;
use Xiag\Rql\Parser\Node\SelectNode;
use Xiag\Rql\Parser\Node\SortNode;
use Xiag\Rql\Parser\Query;

/**
 * Functional tests for ElasticsearchAggregationBuilder.
 *
 * Tests building of Elasticsearch aggregations from RQL queries.
 * Analogous to RqlConditionBuilderTest style.
 */
class ElasticsearchAggregationBuilderTest extends TestCase
{
    private function createBuilder(): ElasticsearchAggregationBuilder
    {
        return new ElasticsearchAggregationBuilder();
    }

    /**
     * Data provider for shouldUseNativeAggregations tests
     */
    public function shouldUseNativeAggregationsProvider(): \Generator
    {
        yield 'query with GROUP BY' => [
            new RqlQuery('select(field1)&groupby(field1)'),
            true,
        ];

        yield 'query with aggregate SELECT only' => [
            new RqlQuery('select(count(id),max(price))'),
            true,
        ];

        yield 'query with mixed SELECT (aggregate + plain)' => [
            new RqlQuery('select(id,count(id))'),
            false,
        ];

        yield 'query without GROUP BY and aggregates' => [
            new RqlQuery('select(id,name)'),
            false,
        ];

        yield 'query with plain SELECT only' => [
            new Query(),
            false,
        ];
    }

    /**
     * @dataProvider shouldUseNativeAggregationsProvider
     */
    public function testShouldUseNativeAggregations(Query $query, bool $expected): void
    {
        $builder = $this->createBuilder();
        $result = $builder->shouldUseNativeAggregations($query);

        $this->assertSame($expected, $result);
    }

    /**
     * Data provider for extractGroupByFields tests
     */
    public function extractGroupByFieldsProvider(): \Generator
    {
        yield 'query with single GROUP BY field' => [
            new RqlQuery('groupby(service)'),
            ['service'],
        ];

        yield 'query with multiple GROUP BY fields' => [
            new RqlQuery('groupby(service,level)'),
            ['service', 'level'],
        ];

        yield 'query without GROUP BY' => [
            new Query(),
            [],
        ];

        yield 'query with duplicate GROUP BY fields' => [
            new RqlQuery('groupby(service,service,level)'),
            ['service', 'level'],
        ];
    }

    /**
     * @dataProvider extractGroupByFieldsProvider
     */
    public function testExtractGroupByFields(Query $query, array $expected): void
    {
        $builder = $this->createBuilder();
        $result = $builder->extractGroupByFields($query);

        $this->assertSame($expected, $result);
    }

    /**
     * Data provider for hasAggregateSelect tests
     */
    public function hasAggregateSelectProvider(): \Generator
    {
        yield 'SELECT with aggregate functions' => [
            new SelectNode([
                new AggregateFunctionNode('count', 'id'),
                new AggregateFunctionNode('max', 'price'),
            ]),
            true,
        ];

        yield 'SELECT with plain fields only' => [
            new SelectNode(['id', 'name', 'surname']),
            false,
        ];

        yield 'SELECT with mixed fields' => [
            new SelectNode([
                'id',
                new AggregateFunctionNode('count', 'id'),
            ]),
            true,
        ];

        yield 'null SELECT' => [
            null,
            false,
        ];

        yield 'empty SELECT' => [
            new SelectNode([]),
            false,
        ];
    }

    /**
     * @dataProvider hasAggregateSelectProvider
     */
    public function testHasAggregateSelect(?SelectNode $selectNode, bool $expected): void
    {
        $builder = $this->createBuilder();
        $result = $builder->hasAggregateSelect($selectNode);

        $this->assertSame($expected, $result);
    }

    /**
     * Data provider for hasPlainSelectFields tests
     */
    public function hasPlainSelectFieldsProvider(): \Generator
    {
        yield 'SELECT with plain fields' => [
            new SelectNode(['id', 'name']),
            true,
        ];

        yield 'SELECT with aggregate functions only' => [
            new SelectNode([
                new AggregateFunctionNode('count', 'id'),
            ]),
            false,
        ];

        yield 'SELECT with mixed fields' => [
            new SelectNode([
                'id',
                new AggregateFunctionNode('count', 'id'),
            ]),
            true,
        ];

        yield 'null SELECT' => [
            null,
            false,
        ];
    }

    /**
     * @dataProvider hasPlainSelectFieldsProvider
     */
    public function testHasPlainSelectFields(?SelectNode $selectNode, bool $expected): void
    {
        $builder = $this->createBuilder();
        $result = $builder->hasPlainSelectFields($selectNode);

        $this->assertSame($expected, $result);
    }

    /**
     * Test buildSelectDescriptors for GROUP BY query
     */
    public function testBuildSelectDescriptorsForGroupBy(): void
    {
        $query = new RqlQuery('select(service,count(id),max(id))&groupby(service)');
        $groupFields = ['service'];

        $builder = $this->createBuilder();
        $descriptors = $builder->buildSelectDescriptors($query, $groupFields);

        $expected = [
            [
                'type' => 'group',
                'field' => 'service',
                'label' => 'service',
            ],
            [
                'type' => 'metric',
                'function' => 'count',
                'field' => 'id',
                'label' => 'count(id)',
            ],
            [
                'type' => 'metric',
                'function' => 'max',
                'field' => 'id',
                'label' => 'max(id)',
            ],
        ];

        $this->assertEquals($expected, $descriptors);
    }

    /**
     * Test buildSelectDescriptors converts non-grouped fields to count
     */
    public function testBuildSelectDescriptorsConvertsNonGroupedFieldToCount(): void
    {
        $query = new RqlQuery('select(service,id)&groupby(service)');
        $groupFields = ['service'];

        $builder = $this->createBuilder();
        $descriptors = $builder->buildSelectDescriptors($query, $groupFields);

        $expected = [
            [
                'type' => 'group',
                'field' => 'service',
                'label' => 'service',
            ],
            [
                'type' => 'metric',
                'function' => 'count',
                'field' => 'id',
                'label' => 'count(id)',
            ],
        ];

        $this->assertEquals($expected, $descriptors);
    }

    /**
     * Test buildSelectDescriptors without SELECT returns group fields
     */
    public function testBuildSelectDescriptorsWithoutSelectReturnsGroupFields(): void
    {
        $query = new RqlQuery('groupby(service,level)');
        $groupFields = ['service', 'level'];

        $builder = $this->createBuilder();
        $descriptors = $builder->buildSelectDescriptors($query, $groupFields);

        $expected = [
            [
                'type' => 'group',
                'field' => 'service',
                'label' => 'service',
            ],
            [
                'type' => 'group',
                'field' => 'level',
                'label' => 'level',
            ],
        ];

        $this->assertEquals($expected, $descriptors);
    }

    /**
     * Test unsupported aggregate function throws exception
     */
    public function testBuildSelectDescriptorsThrowsExceptionForUnsupportedFunction(): void
    {
        $query = new Query();
        $query->setSelect(new SelectNode([
            new AggregateFunctionNode('median', 'price'),
        ]));

        $builder = $this->createBuilder();

        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage('Unsupported aggregate function: median');

        $builder->buildSelectDescriptors($query, []);
    }

    /**
     * Test attachMetricAliases
     */
    public function testAttachMetricAliases(): void
    {
        $descriptors = [
            ['type' => 'group', 'field' => 'service'],
            ['type' => 'metric', 'function' => 'count', 'field' => 'id'],
            ['type' => 'metric', 'function' => 'max', 'field' => 'price'],
            ['type' => 'group', 'field' => 'level'],
            ['type' => 'metric', 'function' => 'sum', 'field' => 'amount'],
        ];

        $builder = $this->createBuilder();
        $result = $builder->attachMetricAliases($descriptors);

        $this->assertSame('metric_0', $result[1]['alias']);
        $this->assertSame('metric_1', $result[2]['alias']);
        $this->assertSame('metric_2', $result[4]['alias']);
        $this->assertArrayNotHasKey('alias', $result[0]);
        $this->assertArrayNotHasKey('alias', $result[3]);
    }

    /**
     * Data provider for buildMetricAggregations tests
     */
    public function buildMetricAggregationsProvider(): \Generator
    {
        yield 'count aggregation on identifier field (id)' => [
            [
                ['type' => 'metric', 'function' => 'count', 'field' => 'id', 'alias' => 'metric_0'],
            ],
            [
                'metric_0' => ['filter' => ['match_all' => (object) []]], // id is the identifier by default
            ],
        ];

        yield 'count on non-identifier field' => [
            [
                ['type' => 'metric', 'function' => 'count', 'field' => 'status', 'alias' => 'metric_0'],
            ],
            [
                'metric_0' => ['filter' => ['exists' => ['field' => 'status']]],
            ],
        ];

        yield 'max aggregation' => [
            [
                ['type' => 'metric', 'function' => 'max', 'field' => 'price', 'alias' => 'metric_0'],
            ],
            [
                'metric_0' => ['max' => ['field' => 'price']],
            ],
        ];

        yield 'min aggregation' => [
            [
                ['type' => 'metric', 'function' => 'min', 'field' => 'price', 'alias' => 'metric_0'],
            ],
            [
                'metric_0' => ['min' => ['field' => 'price']],
            ],
        ];

        yield 'sum aggregation' => [
            [
                ['type' => 'metric', 'function' => 'sum', 'field' => 'amount', 'alias' => 'metric_0'],
            ],
            [
                'metric_0' => ['sum' => ['field' => 'amount']],
            ],
        ];

        yield 'avg aggregation' => [
            [
                ['type' => 'metric', 'function' => 'avg', 'field' => 'score', 'alias' => 'metric_0'],
            ],
            [
                'metric_0' => ['avg' => ['field' => 'score']],
            ],
        ];

        yield 'multiple aggregations' => [
            [
                ['type' => 'metric', 'function' => 'count', 'field' => 'id', 'alias' => 'metric_0'],
                ['type' => 'metric', 'function' => 'max', 'field' => 'price', 'alias' => 'metric_1'],
                ['type' => 'metric', 'function' => 'sum', 'field' => 'amount', 'alias' => 'metric_2'],
            ],
            [
                'metric_0' => ['filter' => ['match_all' => (object) []]], // id is the identifier
                'metric_1' => ['max' => ['field' => 'price']],
                'metric_2' => ['sum' => ['field' => 'amount']],
            ],
        ];
    }

    /**
     * @dataProvider buildMetricAggregationsProvider
     */
    public function testBuildMetricAggregations(array $descriptors, array $expected): void
    {
        $builder = $this->createBuilder();
        $result = $builder->buildMetricAggregations($descriptors);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test buildGroupSources without sort
     */
    public function testBuildGroupSourcesWithoutSort(): void
    {
        $groupFields = ['service', 'level'];

        $builder = $this->createBuilder();
        $result = $builder->buildGroupSources($groupFields, null);

        $expected = [
            'sources' => [
                [
                    'group_0' => [
                        'terms' => [
                            'field' => 'service',
                            'order' => 'asc',
                            'missing_bucket' => true,
                        ],
                    ],
                ],
                [
                    'group_1' => [
                        'terms' => [
                            'field' => 'level',
                            'order' => 'asc',
                            'missing_bucket' => true,
                        ],
                    ],
                ],
            ],
            'byField' => [
                'service' => 'group_0',
                'level' => 'group_1',
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test buildGroupSources with sort
     */
    public function testBuildGroupSourcesWithSort(): void
    {
        $groupFields = ['service', 'level'];
        $sortNode = new SortNode([
            'service' => SortNode::SORT_DESC,
            'level' => SortNode::SORT_ASC,
        ]);

        $builder = $this->createBuilder();
        $result = $builder->buildGroupSources($groupFields, $sortNode);

        $expected = [
            'sources' => [
                [
                    'group_0' => [
                        'terms' => [
                            'field' => 'service',
                            'order' => 'desc',
                            'missing_bucket' => true,
                        ],
                    ],
                ],
                [
                    'group_1' => [
                        'terms' => [
                            'field' => 'level',
                            'order' => 'asc',
                            'missing_bucket' => true,
                        ],
                    ],
                ],
            ],
            'byField' => [
                'service' => 'group_0',
                'level' => 'group_1',
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test buildGroupSources throws exception for invalid sort direction
     */
    public function testBuildGroupSourcesThrowsExceptionForInvalidSortDirection(): void
    {
        $groupFields = ['service'];
        $sortNode = new SortNode(['service' => 99]); // Invalid direction

        $builder = $this->createBuilder();

        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage('Invalid sort direction: 99');

        $builder->buildGroupSources($groupFields, $sortNode);
    }

    // ========================================
    // Edge Case Tests
    // ========================================

    /**
     * Edge case: Empty groupFields array.
     */
    public function testBuildGroupSourcesWithEmptyGroupFields(): void
    {
        $builder = $this->createBuilder();
        $result = $builder->buildGroupSources([], null);

        $expected = [
            'sources' => [],
            'byField' => [],
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Edge case: buildMetricAggregations with empty alias should skip.
     */
    public function testBuildMetricAggregationsSkipsEmptyAlias(): void
    {
        $selectDescriptors = [
            ['type' => 'metric', 'function' => 'sum', 'field' => 'price', 'alias' => ''],
            ['type' => 'metric', 'function' => 'count', 'field' => 'status', 'alias' => 'metric_0'],
        ];

        $builder = $this->createBuilder();
        $result = $builder->buildMetricAggregations($selectDescriptors);

        $expected = [
            'metric_0' => ['filter' => ['exists' => ['field' => 'status']]],
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Edge case: buildMetricAggregations with missing alias should skip.
     */
    public function testBuildMetricAggregationsSkipsMissingAlias(): void
    {
        $selectDescriptors = [
            ['type' => 'metric', 'function' => 'sum', 'field' => 'price'],
            ['type' => 'metric', 'function' => 'count', 'field' => 'status', 'alias' => 'metric_0'],
        ];

        $builder = $this->createBuilder();
        $result = $builder->buildMetricAggregations($selectDescriptors);

        $expected = [
            'metric_0' => ['filter' => ['exists' => ['field' => 'status']]],
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Edge case: count() on identifier field uses match_all.
     */
    public function testBuildMetricAggregationsCountOnIdentifierUsesMatchAll(): void
    {
        $selectDescriptors = [
            ['type' => 'metric', 'function' => 'count', 'field' => 'id', 'alias' => 'metric_0'],
        ];

        $builder = $this->createBuilder();
        $result = $builder->buildMetricAggregations($selectDescriptors);

        $expected = [
            'metric_0' => ['filter' => ['match_all' => (object) []]],
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Edge case: count() on _id field (common Elasticsearch identifier).
     */
    public function testBuildMetricAggregationsCountOnElasticsearchIdField(): void
    {
        $selectDescriptors = [
            ['type' => 'metric', 'function' => 'count', 'field' => '_id', 'alias' => 'metric_0'],
        ];

        $builder = new ElasticsearchAggregationBuilder('_id'); // _id as identifier
        $result = $builder->buildMetricAggregations($selectDescriptors);

        $expected = [
            'metric_0' => ['filter' => ['match_all' => (object) []]], // Should use match_all
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Edge case: count() on non-identifier field uses exists filter.
     */
    public function testBuildMetricAggregationsCountOnNonIdentifierUsesExists(): void
    {
        $selectDescriptors = [
            ['type' => 'metric', 'function' => 'count', 'field' => 'status', 'alias' => 'metric_0'],
        ];

        $builder = $this->createBuilder(); // identifier='id'
        $result = $builder->buildMetricAggregations($selectDescriptors);

        $expected = [
            'metric_0' => ['filter' => ['exists' => ['field' => 'status']]], // Should use exists
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Edge case: attachMetricAliases with no metrics.
     */
    public function testAttachMetricAliasesWithNoMetrics(): void
    {
        $selectDescriptors = [
            ['type' => 'group', 'field' => 'category', 'label' => 'category'],
        ];

        $builder = $this->createBuilder();
        $result = $builder->attachMetricAliases($selectDescriptors);

        $expected = [
            ['type' => 'group', 'field' => 'category', 'label' => 'category'],
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Edge case: attachMetricAliases increments index correctly.
     */
    public function testAttachMetricAliasesIncrementsIndexCorrectly(): void
    {
        $selectDescriptors = [
            ['type' => 'group', 'field' => 'category', 'label' => 'category'],
            ['type' => 'metric', 'function' => 'sum', 'field' => 'price', 'label' => 'total'],
            ['type' => 'metric', 'function' => 'count', 'field' => 'id', 'label' => 'count'],
            ['type' => 'group', 'field' => 'brand', 'label' => 'brand'],
            ['type' => 'metric', 'function' => 'avg', 'field' => 'rating', 'label' => 'avg_rating'],
        ];

        $builder = $this->createBuilder();
        $result = $builder->attachMetricAliases($selectDescriptors);

        $expected = [
            ['type' => 'group', 'field' => 'category', 'label' => 'category'],
            ['type' => 'metric', 'function' => 'sum', 'field' => 'price', 'label' => 'total', 'alias' => 'metric_0'],
            ['type' => 'metric', 'function' => 'count', 'field' => 'id', 'label' => 'count', 'alias' => 'metric_1'],
            ['type' => 'group', 'field' => 'brand', 'label' => 'brand'],
            ['type' => 'metric', 'function' => 'avg', 'field' => 'rating', 'label' => 'avg_rating', 'alias' => 'metric_2'],
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Edge case: buildSelectDescriptors skips empty string fields with groupFields.
     */
    public function testBuildSelectDescriptorsSkipsEmptyStringFields(): void
    {
        $query = new Query();
        $query->setSelect(new SelectNode(['name', '', 'price']));
        $groupFields = ['name', 'price'];

        $builder = $this->createBuilder();
        $result = $builder->buildSelectDescriptors($query, $groupFields);

        $expected = [
            ['type' => 'group', 'field' => 'name', 'label' => 'name'],
            ['type' => 'group', 'field' => 'price', 'label' => 'price'],
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Edge case: buildGroupSources with sort on non-grouped field.
     */
    public function testBuildGroupSourcesWithSortOnNonGroupedField(): void
    {
        $groupFields = ['category', 'brand'];
        $sortNode = new SortNode([
            'category' => SortNode::SORT_ASC,
            'price' => SortNode::SORT_DESC, // Not in groupFields
            'brand' => SortNode::SORT_DESC,
        ]);

        $builder = $this->createBuilder();
        $result = $builder->buildGroupSources($groupFields, $sortNode);

        $expected = [
            'sources' => [
                [
                    'group_0' => [
                        'terms' => [
                            'field' => 'category',
                            'order' => 'asc',
                            'missing_bucket' => true,
                        ],
                    ],
                ],
                [
                    'group_1' => [
                        'terms' => [
                            'field' => 'brand',
                            'order' => 'desc',
                            'missing_bucket' => true,
                        ],
                    ],
                ],
            ],
            'byField' => [
                'category' => 'group_0',
                'brand' => 'group_1',
            ],
        ];

        $this->assertEquals($expected, $result);
    }


    /**
     * Edge case: buildSelectDescriptors with all aggregate functions.
     */
    public function testBuildSelectDescriptorsWithAllAggregateFunctions(): void
    {
        $query = new Query();
        $query->setSelect(new SelectNode([
            new AggregateFunctionNode('count', 'id'),
            new AggregateFunctionNode('sum', 'price'),
            new AggregateFunctionNode('avg', 'rating'),
            new AggregateFunctionNode('min', 'weight'),
            new AggregateFunctionNode('max', 'height'),
        ]));

        $builder = $this->createBuilder();
        $descriptors = $builder->buildSelectDescriptors($query, []);

        $this->assertCount(5, $descriptors);
        $this->assertEquals('count', $descriptors[0]['function']);
        $this->assertEquals('sum', $descriptors[1]['function']);
        $this->assertEquals('avg', $descriptors[2]['function']);
        $this->assertEquals('min', $descriptors[3]['function']);
        $this->assertEquals('max', $descriptors[4]['function']);
    }

    /**
     * Edge case: buildMetricAggregations for all metric types.
     */
    public function testBuildMetricAggregationsForAllMetricTypes(): void
    {
        $selectDescriptors = [
            ['type' => 'metric', 'function' => 'count', 'field' => 'id', 'alias' => 'metric_0'],
            ['type' => 'metric', 'function' => 'sum', 'field' => 'price', 'alias' => 'metric_1'],
            ['type' => 'metric', 'function' => 'avg', 'field' => 'rating', 'alias' => 'metric_2'],
            ['type' => 'metric', 'function' => 'min', 'field' => 'weight', 'alias' => 'metric_3'],
            ['type' => 'metric', 'function' => 'max', 'field' => 'height', 'alias' => 'metric_4'],
        ];

        $builder = $this->createBuilder();
        $result = $builder->buildMetricAggregations($selectDescriptors);

        $this->assertArrayHasKey('metric_0', $result);
        $this->assertArrayHasKey('metric_1', $result);
        $this->assertArrayHasKey('metric_2', $result);
        $this->assertArrayHasKey('metric_3', $result);
        $this->assertArrayHasKey('metric_4', $result);

        // Count on 'id' (identifier) uses match_all
        $this->assertEquals(['filter' => ['match_all' => (object) []]], $result['metric_0']);
        $this->assertEquals(['sum' => ['field' => 'price']], $result['metric_1']);
        $this->assertEquals(['avg' => ['field' => 'rating']], $result['metric_2']);
        $this->assertEquals(['min' => ['field' => 'weight']], $result['metric_3']);
        $this->assertEquals(['max' => ['field' => 'height']], $result['metric_4']);
    }
}
