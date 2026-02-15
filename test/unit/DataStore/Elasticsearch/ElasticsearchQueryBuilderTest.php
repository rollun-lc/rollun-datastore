<?php

declare(strict_types=1);

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\unit\DataStore\Elasticsearch;

use Elasticsearch\Client;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use rollun\datastore\DataStore\Elasticsearch\ElasticsearchQueryBuilder;

/**
 * Unit tests for ElasticsearchQueryBuilder edge cases.
 *
 * Note: These tests focus on internal helper methods and edge case logic.
 * Full integration tests with Elasticsearch are in functional tests.
 */
class ElasticsearchQueryBuilderTest extends TestCase
{
    private function createBuilder(): ElasticsearchQueryBuilder
    {
        $client = $this->createMock(Client::class);
        $client->method('indices')->willReturn($this->createMock(\Elasticsearch\Namespaces\IndicesNamespace::class));

        return new ElasticsearchQueryBuilder($client, 'test-index', 'id', new NullLogger());
    }

    /**
     * Test calculateBatchSize with remaining = 0.
     */
    public function testCalculateBatchSizeWithZeroRemaining(): void
    {
        $builder = $this->createBuilder();
        $method = new \ReflectionMethod($builder, 'calculateBatchSize');
        $method->setAccessible(true);

        $result = $method->invoke($builder, 0, 0, 0);

        $this->assertSame(0, $result);
    }

    /**
     * Test calculateBatchSize with remaining = null (unlimited).
     */
    public function testCalculateBatchSizeWithUnlimitedRemaining(): void
    {
        $builder = $this->createBuilder();
        $method = new \ReflectionMethod($builder, 'calculateBatchSize');
        $method->setAccessible(true);

        $result = $method->invoke($builder, null, 0, 0);

        // Should return SEARCH_BATCH_SIZE (500)
        $this->assertSame(500, $result);
    }

    /**
     * Test calculateBatchSize when remaining is less than batch size.
     */
    public function testCalculateBatchSizeWithSmallRemaining(): void
    {
        $builder = $this->createBuilder();
        $method = new \ReflectionMethod($builder, 'calculateBatchSize');
        $method->setAccessible(true);

        $result = $method->invoke($builder, 10, 0, 0);

        $this->assertSame(10, $result);
    }

    /**
     * Test calculateBatchSize with offset > skipped (need to skip more).
     */
    public function testCalculateBatchSizeWithOffsetNeedingMoreSkip(): void
    {
        $builder = $this->createBuilder();
        $method = new \ReflectionMethod($builder, 'calculateBatchSize');
        $method->setAccessible(true);

        // offset=100, skipped=50, remaining=20
        // needToSkip = 100 - 50 = 50
        // needToTake = 20
        // result = min(500, 50 + 20) = 70
        $result = $method->invoke($builder, 20, 100, 50);

        $this->assertSame(70, $result);
    }

    /**
     * Test calculateBatchSize when needToSkip + needToTake exceeds batch size.
     */
    public function testCalculateBatchSizeExceedsBatchSize(): void
    {
        $builder = $this->createBuilder();
        $method = new \ReflectionMethod($builder, 'calculateBatchSize');
        $method->setAccessible(true);

        // offset=1000, skipped=0, remaining=1000
        // needToSkip = 1000
        // needToTake = 1000
        // result = min(500, 1000 + 1000) = 500 (capped at SEARCH_BATCH_SIZE)
        $result = $method->invoke($builder, 1000, 1000, 0);

        $this->assertSame(500, $result);
    }

    /**
     * Test calculateBatchSize with offset already satisfied.
     */
    public function testCalculateBatchSizeWithOffsetAlreadySatisfied(): void
    {
        $builder = $this->createBuilder();
        $method = new \ReflectionMethod($builder, 'calculateBatchSize');
        $method->setAccessible(true);

        // offset=50, skipped=100 (already skipped more than needed)
        // needToSkip = 0
        // needToTake = 20
        // result = min(500, 0 + 20) = 20
        $result = $method->invoke($builder, 20, 50, 100);

        $this->assertSame(20, $result);
    }

    /**
     * Test calculateBatchSize edge case: minimum result is 1.
     */
    public function testCalculateBatchSizeMinimumIsOne(): void
    {
        $builder = $this->createBuilder();
        $method = new \ReflectionMethod($builder, 'calculateBatchSize');
        $method->setAccessible(true);

        // Any positive remaining should give at least 1
        $result = $method->invoke($builder, 1, 0, 0);

        $this->assertGreaterThanOrEqual(1, $result);
    }

    /**
     * Test resolveLimit with null limitNode returns default.
     */
    public function testResolveLimitWithNullReturnsDefault(): void
    {
        $builder = $this->createBuilder();
        $method = new \ReflectionMethod($builder, 'resolveLimit');
        $method->setAccessible(true);

        $result = $method->invoke($builder, null);

        // DEFAULT_QUERY_LIMIT = 10000
        $this->assertSame(10000, $result);
    }

    /**
     * Test extractSelectFields with null SelectNode.
     */
    public function testExtractSelectFieldsWithNull(): void
    {
        $builder = $this->createBuilder();
        $method = new \ReflectionMethod($builder, 'extractSelectFields');
        $method->setAccessible(true);

        $result = $method->invoke($builder, null);

        $this->assertSame([], $result);
    }

    /**
     * Test extractSelectFields with empty SelectNode.
     */
    public function testExtractSelectFieldsWithEmptySelect(): void
    {
        $selectNode = new \Xiag\Rql\Parser\Node\SelectNode([]);

        $builder = $this->createBuilder();
        $method = new \ReflectionMethod($builder, 'extractSelectFields');
        $method->setAccessible(true);

        $result = $method->invoke($builder, $selectNode);

        $this->assertSame([], $result);
    }

    /**
     * Test extractSelectFields removes duplicates.
     */
    public function testExtractSelectFieldsRemovesDuplicates(): void
    {
        $selectNode = new \Xiag\Rql\Parser\Node\SelectNode(['name', 'price', 'name', 'category']);

        $builder = $this->createBuilder();
        $method = new \ReflectionMethod($builder, 'extractSelectFields');
        $method->setAccessible(true);

        $result = $method->invoke($builder, $selectNode);

        $expected = ['name', 'price', 'category'];
        $this->assertSame($expected, $result);
    }

    /**
     * Test extractSelectFields skips empty strings.
     */
    public function testExtractSelectFieldsSkipsEmptyStrings(): void
    {
        $selectNode = new \Xiag\Rql\Parser\Node\SelectNode(['name', '', 'price']);

        $builder = $this->createBuilder();
        $method = new \ReflectionMethod($builder, 'extractSelectFields');
        $method->setAccessible(true);

        $result = $method->invoke($builder, $selectNode);

        $expected = ['name', 'price'];
        $this->assertSame($expected, $result);
    }

    /**
     * Test extractSelectFields with nested fields (dots).
     */
    public function testExtractSelectFieldsWithNestedFields(): void
    {
        $selectNode = new \Xiag\Rql\Parser\Node\SelectNode([
            'user.name',
            'product.price',
            'category.parent.name',
        ]);

        $builder = $this->createBuilder();
        $method = new \ReflectionMethod($builder, 'extractSelectFields');
        $method->setAccessible(true);

        $result = $method->invoke($builder, $selectNode);

        $expected = ['user.name', 'product.price', 'category.parent.name'];
        $this->assertSame($expected, $result);
    }

    /**
     * Test shouldProcessSelectInMemory returns false when no aggregate select.
     */
    public function testShouldProcessSelectInMemoryWithNoAggregates(): void
    {
        $query = new \Xiag\Rql\Parser\Query();
        $query->setSelect(new \Xiag\Rql\Parser\Node\SelectNode(['name', 'price']));

        $builder = $this->createBuilder();
        $method = new \ReflectionMethod($builder, 'shouldProcessSelectInMemory');
        $method->setAccessible(true);

        $result = $method->invoke($builder, $query);

        $this->assertFalse($result);
    }

    /**
     * Test shouldProcessSelectInMemory returns false when GROUP BY exists.
     */
    public function testShouldProcessSelectInMemoryWithGroupBy(): void
    {
        $query = new \rollun\datastore\Rql\RqlQuery('select(name,count(id))&groupby(name)');

        $builder = $this->createBuilder();
        $method = new \ReflectionMethod($builder, 'shouldProcessSelectInMemory');
        $method->setAccessible(true);

        $result = $method->invoke($builder, $query);

        $this->assertFalse($result);
    }

    /**
     * Edge case documentation: calculateBatchSize math verification.
     */
    public function testCalculateBatchSizeMathEdgeCases(): void
    {
        $builder = $this->createBuilder();
        $method = new \ReflectionMethod($builder, 'calculateBatchSize');
        $method->setAccessible(true);

        // Edge case 1: remaining=1, offset=0, skipped=0 → should return 1
        $this->assertSame(1, $method->invoke($builder, 1, 0, 0));

        // Edge case 2: remaining=500, offset=0, skipped=0 → should return 500
        $this->assertSame(500, $method->invoke($builder, 500, 0, 0));

        // Edge case 3: remaining=1000, offset=0, skipped=0 → should cap at 500
        $this->assertSame(500, $method->invoke($builder, 1000, 0, 0));

        // Edge case 4: remaining=50, offset=100, skipped=99 → needToSkip=1, result=51
        $this->assertSame(51, $method->invoke($builder, 50, 100, 99));

        // Edge case 5: remaining=null (unlimited), offset=200, skipped=100 → needToSkip=100, result=min(500, 100+500)=500
        $this->assertSame(500, $method->invoke($builder, null, 200, 100));
    }

    /**
     * Edge case: Very large offset.
     */
    public function testCalculateBatchSizeWithVeryLargeOffset(): void
    {
        $builder = $this->createBuilder();
        $method = new \ReflectionMethod($builder, 'calculateBatchSize');
        $method->setAccessible(true);

        // offset=100000, skipped=0, remaining=10
        // needToSkip = 100000
        // needToTake = 10
        // result = min(500, 100000 + 10) = 500 (capped)
        $result = $method->invoke($builder, 10, 100000, 0);

        $this->assertSame(500, $result);
    }

    /**
     * Test extractSelectFields with multiple fields.
     */
    public function testExtractSelectFieldsWithMultipleFields(): void
    {
        $selectNode = new \Xiag\Rql\Parser\Node\SelectNode(['field1', 'field2', 'field3']);

        $builder = $this->createBuilder();
        $method = new \ReflectionMethod($builder, 'extractSelectFields');
        $method->setAccessible(true);

        $result = $method->invoke($builder, $selectNode);

        $expected = ['field1', 'field2', 'field3'];
        $this->assertSame($expected, $result);
    }

    /**
     * Edge case: calculateBatchSize when offset is negative (should be treated as 0).
     */
    public function testCalculateBatchSizeWithNegativeOffset(): void
    {
        $builder = $this->createBuilder();
        $method = new \ReflectionMethod($builder, 'calculateBatchSize');
        $method->setAccessible(true);

        // Even if offset is negative, max(0, offset - skipped) ensures non-negative
        // offset=-10, skipped=0, remaining=20
        // needToSkip = max(0, -10 - 0) = 0
        $result = $method->invoke($builder, 20, -10, 0);

        $this->assertSame(20, $result);
    }

    /**
     * Edge case: calculateBatchSize when skipped exceeds offset by large margin.
     */
    public function testCalculateBatchSizeWhenSkippedExceedsOffset(): void
    {
        $builder = $this->createBuilder();
        $method = new \ReflectionMethod($builder, 'calculateBatchSize');
        $method->setAccessible(true);

        // offset=10, skipped=1000, remaining=5
        // needToSkip = max(0, 10 - 1000) = 0
        // needToTake = 5
        // result = min(500, 0 + 5) = 5
        $result = $method->invoke($builder, 5, 10, 1000);

        $this->assertSame(5, $result);
    }

    /**
     * Test extractSelectFields with special characters in field names.
     */
    public function testExtractSelectFieldsWithSpecialCharacters(): void
    {
        $selectNode = new \Xiag\Rql\Parser\Node\SelectNode([
            '_id',
            '_score',
            '@timestamp',
            'field-with-dash',
            'field_with_underscore',
        ]);

        $builder = $this->createBuilder();
        $method = new \ReflectionMethod($builder, 'extractSelectFields');
        $method->setAccessible(true);

        $result = $method->invoke($builder, $selectNode);

        $expected = ['_id', '_score', '@timestamp', 'field-with-dash', 'field_with_underscore'];
        $this->assertSame($expected, $result);
    }

    /**
     * Edge case: calculateBatchSize boundary testing.
     */
    public function testCalculateBatchSizeBoundaryValues(): void
    {
        $builder = $this->createBuilder();
        $method = new \ReflectionMethod($builder, 'calculateBatchSize');
        $method->setAccessible(true);

        // Test at SEARCH_BATCH_SIZE boundary (500)
        $this->assertSame(500, $method->invoke($builder, 500, 0, 0));
        $this->assertSame(499, $method->invoke($builder, 499, 0, 0));
        $this->assertSame(500, $method->invoke($builder, 501, 0, 0)); // Capped at 500
    }

    /**
     * Test resolveLimit with LimitNode containing zero.
     */
    public function testResolveLimitWithZero(): void
    {
        $limitNode = new \Xiag\Rql\Parser\Node\LimitNode(0, 0);

        $builder = $this->createBuilder();
        $method = new \ReflectionMethod($builder, 'resolveLimit');
        $method->setAccessible(true);

        $result = $method->invoke($builder, $limitNode);

        $this->assertSame(0, $result);
    }

    /**
     * Test resolveLimit with LimitNode containing negative value.
     */
    public function testResolveLimitWithNegative(): void
    {
        $limitNode = new \Xiag\Rql\Parser\Node\LimitNode(-10, 0);

        $builder = $this->createBuilder();
        $method = new \ReflectionMethod($builder, 'resolveLimit');
        $method->setAccessible(true);

        $result = $method->invoke($builder, $limitNode);

        $this->assertSame(-10, $result);
        // Note: Negative limits are validated elsewhere in the code
    }

    /**
     * Test extractSelectFields maintains order.
     */
    public function testExtractSelectFieldsMaintainsOrder(): void
    {
        $selectNode = new \Xiag\Rql\Parser\Node\SelectNode(['z_field', 'a_field', 'm_field']);

        $builder = $this->createBuilder();
        $method = new \ReflectionMethod($builder, 'extractSelectFields');
        $method->setAccessible(true);

        $result = $method->invoke($builder, $selectNode);

        // Order should be preserved (not alphabetically sorted)
        $expected = ['z_field', 'a_field', 'm_field'];
        $this->assertSame($expected, $result);
    }

    /**
     * Documentation test: LIMIT_INFINITY constant value.
     */
    public function testLimitInfinityConstant(): void
    {
        // LIMIT_INFINITY is defined in DataStoreAbstract
        $infinityValue = \rollun\datastore\DataStore\DataStoreAbstract::LIMIT_INFINITY;

        // Should be a large number (typically -1 or PHP_INT_MAX)
        $this->assertIsInt($infinityValue);
    }
}
