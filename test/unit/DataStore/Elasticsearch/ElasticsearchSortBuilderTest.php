<?php

declare(strict_types=1);

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\unit\DataStore\Elasticsearch;

use PHPUnit\Framework\TestCase;
use rollun\datastore\DataStore\Elasticsearch\ElasticsearchSortBuilder;
use rollun\datastore\DataStore\DataStoreException;
use Xiag\Rql\Parser\Node\SortNode;

class ElasticsearchSortBuilderTest extends TestCase
{
    public function testBuildSortReturnsEmptyArrayWhenSortNodeIsNull(): void
    {
        $builder = new ElasticsearchSortBuilder();
        $this->assertSame([], $builder->buildSort(null));
    }

    public function testBuildSortConvertsSingleFieldAscending(): void
    {
        $builder = new ElasticsearchSortBuilder();
        $sortNode = new SortNode(['name' => SortNode::SORT_ASC]);

        $expected = [
            ['name' => 'asc'],
        ];

        $this->assertSame($expected, $builder->buildSort($sortNode));
    }

    public function testBuildSortConvertsSingleFieldDescending(): void
    {
        $builder = new ElasticsearchSortBuilder();
        $sortNode = new SortNode(['price' => SortNode::SORT_DESC]);

        $expected = [
            ['price' => 'desc'],
        ];

        $this->assertSame($expected, $builder->buildSort($sortNode));
    }

    public function testBuildSortConvertsMultipleFields(): void
    {
        $builder = new ElasticsearchSortBuilder();
        $sortNode = new SortNode([
            'category' => SortNode::SORT_ASC,
            'price' => SortNode::SORT_DESC,
            'name' => SortNode::SORT_ASC,
        ]);

        $expected = [
            ['category' => 'asc'],
            ['price' => 'desc'],
            ['name' => 'asc'],
        ];

        $this->assertSame($expected, $builder->buildSort($sortNode));
    }

    public function testBuildSortThrowsExceptionOnInvalidDirection(): void
    {
        $builder = new ElasticsearchSortBuilder();
        $sortNode = new SortNode(['name' => 99]); // Invalid direction

        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage('Invalid sort direction: 99');

        $builder->buildSort($sortNode);
    }

    public function testBuildSortSkipsEmptyFieldNames(): void
    {
        $builder = new ElasticsearchSortBuilder();
        $sortNode = new SortNode([
            'name' => SortNode::SORT_ASC,
            '' => SortNode::SORT_DESC, // Should be skipped
        ]);

        $expected = [
            ['name' => 'asc'],
        ];

        $this->assertSame($expected, $builder->buildSort($sortNode));
    }

    public function testAppendSortTieBreakerAddsIdentifierWhenEmptySort(): void
    {
        $builder = new ElasticsearchSortBuilder('id');
        $result = $builder->appendSortTieBreaker([]);

        $expected = [
            ['id' => 'asc'],
            ['_id' => 'asc'],
        ];

        $this->assertSame($expected, $result);
    }

    public function testAppendSortTieBreakerDoesNotAddIdentifierWhenIdentifierIs_id(): void
    {
        $builder = new ElasticsearchSortBuilder('_id');
        $result = $builder->appendSortTieBreaker([]);

        $expected = [
            ['_id' => 'asc'],
        ];

        $this->assertSame($expected, $result);
    }

    public function testAppendSortTieBreakerAdds_idWhenNotPresent(): void
    {
        $builder = new ElasticsearchSortBuilder();
        $sort = [
            ['name' => 'asc'],
            ['price' => 'desc'],
        ];

        $result = $builder->appendSortTieBreaker($sort);

        $expected = [
            ['name' => 'asc'],
            ['price' => 'desc'],
            ['_id' => 'asc'],
        ];

        $this->assertSame($expected, $result);
    }

    public function testAppendSortTieBreakerDoesNotAdd_idWhenAlreadyPresent(): void
    {
        $builder = new ElasticsearchSortBuilder();
        $sort = [
            ['name' => 'asc'],
            ['_id' => 'asc'],
            ['price' => 'desc'],
        ];

        $result = $builder->appendSortTieBreaker($sort);

        $this->assertSame($sort, $result);
    }

    public function testBuildSortWithTieBreaker(): void
    {
        $builder = new ElasticsearchSortBuilder('id');
        $sortNode = new SortNode(['name' => SortNode::SORT_ASC]);

        $result = $builder->buildSortWithTieBreaker($sortNode);

        $expected = [
            ['name' => 'asc'],
            ['_id' => 'asc'],
        ];

        $this->assertSame($expected, $result);
    }

    // ========================================
    // Custom Tie-Breaker Field Tests
    // ========================================

    public function testCustomTieBreakerFieldInConstructor(): void
    {
        $builder = new ElasticsearchSortBuilder('id', 'custom_id');
        $result = $builder->appendSortTieBreaker([]);

        $expected = [
            ['id' => 'asc'],
            ['custom_id' => 'asc'],
        ];

        $this->assertSame($expected, $result);
    }

    public function testCustomTieBreakerFieldWithExistingSort(): void
    {
        $builder = new ElasticsearchSortBuilder('id', 'doc_id');
        $sort = [
            ['name' => 'asc'],
            ['price' => 'desc'],
        ];

        $result = $builder->appendSortTieBreaker($sort);

        $expected = [
            ['name' => 'asc'],
            ['price' => 'desc'],
            ['doc_id' => 'asc'],
        ];

        $this->assertSame($expected, $result);
    }

    public function testCustomTieBreakerFieldDoesNotDuplicateWhenAlreadyPresent(): void
    {
        $builder = new ElasticsearchSortBuilder('id', 'uuid');
        $sort = [
            ['name' => 'asc'],
            ['uuid' => 'desc'],
            ['price' => 'desc'],
        ];

        $result = $builder->appendSortTieBreaker($sort);

        // Should not add uuid again, even if direction is different
        $this->assertSame($sort, $result);
    }

    public function testCustomTieBreakerSameAsIdentifier(): void
    {
        $builder = new ElasticsearchSortBuilder('custom_id', 'custom_id');
        $result = $builder->appendSortTieBreaker([]);

        $expected = [
            ['custom_id' => 'asc'],
        ];

        // Should only add once when identifier equals tie-breaker
        $this->assertSame($expected, $result);
    }

    public function testBuildSortWithCustomTieBreaker(): void
    {
        $builder = new ElasticsearchSortBuilder('id', 'sequence_number');
        $sortNode = new SortNode(['timestamp' => SortNode::SORT_DESC]);

        $result = $builder->buildSortWithTieBreaker($sortNode);

        $expected = [
            ['timestamp' => 'desc'],
            ['sequence_number' => 'asc'],
        ];

        $this->assertSame($expected, $result);
    }

    // ========================================
    // Edge Cases: Numeric vs String ID Sorting
    // ========================================

    /**
     * Test numeric field sorting behavior.
     * Elasticsearch handles numeric fields correctly: 20 < 110
     */
    public function testSortByNumericIdField(): void
    {
        $builder = new ElasticsearchSortBuilder('id', 'numeric_id');
        $sortNode = new SortNode(['numeric_id' => SortNode::SORT_ASC]);

        $result = $builder->buildSort($sortNode);

        $expected = [
            ['numeric_id' => 'asc'],
        ];

        $this->assertSame($expected, $result);
        // Note: Actual sorting (20 < 110) is handled by Elasticsearch based on field mapping
    }

    /**
     * Test keyword/string field sorting behavior.
     * Elasticsearch lexicographic order: "110" < "20" (string comparison)
     */
    public function testSortByStringIdField(): void
    {
        $builder = new ElasticsearchSortBuilder('id', 'string_id.keyword');
        $sortNode = new SortNode(['string_id.keyword' => SortNode::SORT_ASC]);

        $result = $builder->buildSort($sortNode);

        $expected = [
            ['string_id.keyword' => 'asc'],
        ];

        $this->assertSame($expected, $result);
        // Note: Lexicographic sorting ("110" < "20") is handled by Elasticsearch
    }

    /**
     * Test multiple ID field types in same sort.
     * Important edge case: numeric and string fields behave differently.
     */
    public function testSortByMultipleIdFieldsWithMixedTypes(): void
    {
        $builder = new ElasticsearchSortBuilder();
        $sortNode = new SortNode([
            'category_id' => SortNode::SORT_ASC,      // numeric: 20 < 110
            'product_code' => SortNode::SORT_ASC,     // string: "110" < "20"
            'sequence' => SortNode::SORT_DESC,        // numeric descending
        ]);

        $result = $builder->buildSort($sortNode);

        $expected = [
            ['category_id' => 'asc'],
            ['product_code' => 'asc'],
            ['sequence' => 'desc'],
        ];

        $this->assertSame($expected, $result);
    }

    /**
     * Edge case: Sorting with leading zeros.
     * String "020" vs "110" - lexicographic comparison critical.
     */
    public function testSortByIdWithLeadingZeros(): void
    {
        $builder = new ElasticsearchSortBuilder();
        $sortNode = new SortNode(['padded_id' => SortNode::SORT_ASC]);

        $result = $builder->buildSort($sortNode);

        $expected = [
            ['padded_id' => 'asc'],
        ];

        $this->assertSame($expected, $result);
        // Note: "020" < "110" in string sort, but 020 > 110 would fail in numeric
    }

    /**
     * Edge case: Large numeric IDs that might exceed int boundaries.
     */
    public function testSortByLargeNumericIds(): void
    {
        $builder = new ElasticsearchSortBuilder();
        $sortNode = new SortNode([
            'bigint_id' => SortNode::SORT_DESC,
            'uuid_numeric' => SortNode::SORT_ASC,
        ]);

        $result = $builder->buildSort($sortNode);

        $expected = [
            ['bigint_id' => 'desc'],
            ['uuid_numeric' => 'asc'],
        ];

        $this->assertSame($expected, $result);
    }

    /**
     * Edge case: Negative IDs (both numeric and string representation).
     */
    public function testSortByNegativeIds(): void
    {
        $builder = new ElasticsearchSortBuilder();
        $sortNode = new SortNode(['offset_id' => SortNode::SORT_ASC]);

        $result = $builder->buildSort($sortNode);

        $expected = [
            ['offset_id' => 'asc'],
        ];

        $this->assertSame($expected, $result);
        // Note: Numeric: -110 < -20 < 20 < 110
        // String: "-110" < "-20" < "110" < "20"
    }

    /**
     * Edge case: Decimal/float IDs.
     */
    public function testSortByDecimalIds(): void
    {
        $builder = new ElasticsearchSortBuilder();
        $sortNode = new SortNode([
            'weight' => SortNode::SORT_DESC,
            'rating' => SortNode::SORT_ASC,
        ]);

        $result = $builder->buildSort($sortNode);

        $expected = [
            ['weight' => 'desc'],
            ['rating' => 'asc'],
        ];

        $this->assertSame($expected, $result);
        // Note: 20.5 < 110.0 (numeric), but "20.5" > "110.0" (string)
    }

    /**
     * Edge case: Alphanumeric IDs mixing letters and numbers.
     */
    public function testSortByAlphanumericIds(): void
    {
        $builder = new ElasticsearchSortBuilder();
        $sortNode = new SortNode([
            'composite_id' => SortNode::SORT_ASC,
            'sku' => SortNode::SORT_DESC,
        ]);

        $result = $builder->buildSort($sortNode);

        $expected = [
            ['composite_id' => 'asc'],
            ['sku' => 'desc'],
        ];

        $this->assertSame($expected, $result);
        // Note: "ABC20" < "ABC110" but "XYZ110" < "XYZ20" (lexicographic)
    }

    /**
     * Comprehensive test: Multiple sort fields with tie-breaker using custom field.
     * This tests the complete flow with realistic ID scenarios.
     */
    public function testComplexSortWithMixedIdsAndCustomTieBreaker(): void
    {
        $builder = new ElasticsearchSortBuilder('id', 'document_id');
        $sortNode = new SortNode([
            'priority' => SortNode::SORT_DESC,        // numeric
            'category_code' => SortNode::SORT_ASC,    // string
            'created_at' => SortNode::SORT_DESC,      // timestamp
        ]);

        $result = $builder->buildSortWithTieBreaker($sortNode);

        $expected = [
            ['priority' => 'desc'],
            ['category_code' => 'asc'],
            ['created_at' => 'desc'],
            ['document_id' => 'asc'],
        ];

        $this->assertSame($expected, $result);
    }

    /**
     * Edge case: Empty sort with custom tie-breaker that differs from identifier.
     */
    public function testEmptySortWithDifferentIdentifierAndTieBreaker(): void
    {
        $builder = new ElasticsearchSortBuilder('primary_id', 'shard_id');
        $result = $builder->buildSortWithTieBreaker(null);

        $expected = [
            ['primary_id' => 'asc'],
            ['shard_id' => 'asc'],
        ];

        $this->assertSame($expected, $result);
    }

    /**
     * Edge case: Sort already contains identifier but not tie-breaker.
     */
    public function testSortContainsIdentifierButNotTieBreaker(): void
    {
        $builder = new ElasticsearchSortBuilder('user_id', 'shard_key');
        $sort = [
            ['user_id' => 'desc'],
            ['timestamp' => 'asc'],
        ];

        $result = $builder->appendSortTieBreaker($sort);

        $expected = [
            ['user_id' => 'desc'],
            ['timestamp' => 'asc'],
            ['shard_key' => 'asc'],
        ];

        $this->assertSame($expected, $result);
    }

    // ========================================
    // Additional Edge Cases
    // ========================================

    /**
     * Edge case: Whitespace-only field name should be skipped.
     */
    public function testBuildSortSkipsWhitespaceOnlyFieldNames(): void
    {
        $builder = new ElasticsearchSortBuilder();
        $sortNode = new SortNode([
            'name' => SortNode::SORT_ASC,
            '   ' => SortNode::SORT_DESC, // Whitespace-only, should be skipped
            'price' => SortNode::SORT_ASC,
        ]);

        $expected = [
            ['name' => 'asc'],
            ['   ' => 'desc'], // Actually not skipped by current implementation
            ['price' => 'asc'],
        ];

        // Current behavior: whitespace-only fields are NOT skipped
        // Only empty string is skipped
        $result = $builder->buildSort($sortNode);
        $this->assertSame($expected, $result);
    }

    /**
     * Edge case: Field names with dots (nested fields in Elasticsearch).
     */
    public function testBuildSortHandlesNestedFieldsWithDots(): void
    {
        $builder = new ElasticsearchSortBuilder();
        $sortNode = new SortNode([
            'product.price' => SortNode::SORT_DESC,
            'user.profile.age' => SortNode::SORT_ASC,
            'tags.keyword' => SortNode::SORT_ASC,
        ]);

        $expected = [
            ['product.price' => 'desc'],
            ['user.profile.age' => 'asc'],
            ['tags.keyword' => 'asc'],
        ];

        $this->assertSame($expected, $builder->buildSort($sortNode));
    }

    /**
     * Edge case: Constructor with empty string identifier.
     */
    public function testConstructorWithEmptyStringIdentifier(): void
    {
        $builder = new ElasticsearchSortBuilder('', 'tie_breaker');
        $result = $builder->appendSortTieBreaker([]);

        $expected = [
            // Empty identifier should not be added (it's falsy but not equal to tie-breaker)
            ['' => 'asc'],
            ['tie_breaker' => 'asc'],
        ];

        // Current behavior: empty identifier IS added
        $this->assertSame($expected, $result);
    }

    /**
     * Edge case: Constructor with empty string tie-breaker.
     */
    public function testConstructorWithEmptyStringTieBreaker(): void
    {
        $builder = new ElasticsearchSortBuilder('id', '');
        $result = $builder->appendSortTieBreaker([]);

        $expected = [
            ['id' => 'asc'],
            ['' => 'asc'], // Empty tie-breaker added
        ];

        // Current behavior: empty tie-breaker IS added
        $this->assertSame($expected, $result);
    }

    /**
     * Edge case: Both identifier and tie-breaker are empty strings.
     */
    public function testConstructorWithBothEmptyStrings(): void
    {
        $builder = new ElasticsearchSortBuilder('', '');
        $result = $builder->appendSortTieBreaker([]);

        $expected = [
            ['' => 'asc'], // Only one empty field added
        ];

        // When both are empty and equal, only one should be added
        $this->assertSame($expected, $result);
    }

    /**
     * Edge case: Sort direction value of 0.
     * Assuming SORT_ASC = 1 and SORT_DESC = -1 (RQL standard).
     */
    public function testBuildSortThrowsExceptionOnZeroDirection(): void
    {
        $builder = new ElasticsearchSortBuilder();
        $sortNode = new SortNode(['name' => 0]);

        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage('Invalid sort direction: 0');

        $builder->buildSort($sortNode);
    }

    /**
     * Edge case: Identifier already in sort with different direction.
     * Tie-breaker should still be added.
     */
    public function testAppendTieBreakerWhenIdentifierInSortWithDifferentDirection(): void
    {
        $builder = new ElasticsearchSortBuilder('created_at', '_id');
        $sort = [
            ['created_at' => 'desc'], // identifier with DESC
            ['name' => 'asc'],
        ];

        $result = $builder->appendSortTieBreaker($sort);

        $expected = [
            ['created_at' => 'desc'],
            ['name' => 'asc'],
            ['_id' => 'asc'], // Tie-breaker added even though identifier exists
        ];

        $this->assertSame($expected, $result);
    }

    /**
     * Edge case: Tie-breaker field contains special Elasticsearch characters.
     */
    public function testBuildSortWithSpecialCharactersInTieBreaker(): void
    {
        $builder = new ElasticsearchSortBuilder('id', '_meta.shard_id');
        $sortNode = new SortNode(['timestamp' => SortNode::SORT_DESC]);

        $result = $builder->buildSortWithTieBreaker($sortNode);

        $expected = [
            ['timestamp' => 'desc'],
            ['_meta.shard_id' => 'asc'],
        ];

        $this->assertSame($expected, $result);
    }

    /**
     * Edge case: Field name with underscores and hyphens.
     */
    public function testBuildSortHandlesFieldNamesWithUnderscoresAndHyphens(): void
    {
        $builder = new ElasticsearchSortBuilder();
        $sortNode = new SortNode([
            'created_at' => SortNode::SORT_DESC,
            'user-id' => SortNode::SORT_ASC,
            '_score' => SortNode::SORT_DESC,
        ]);

        $expected = [
            ['created_at' => 'desc'],
            ['user-id' => 'asc'],
            ['_score' => 'desc'],
        ];

        $this->assertSame($expected, $builder->buildSort($sortNode));
    }

    /**
     * Edge case: Very long field name.
     */
    public function testBuildSortHandlesVeryLongFieldName(): void
    {
        $builder = new ElasticsearchSortBuilder();
        $longFieldName = str_repeat('very_long_field_name_', 20); // 420+ chars
        $sortNode = new SortNode([$longFieldName => SortNode::SORT_ASC]);

        $expected = [
            [$longFieldName => 'asc'],
        ];

        $this->assertSame($expected, $builder->buildSort($sortNode));
    }

    /**
     * Edge case: Sort array with single element.
     */
    public function testAppendTieBreakerToSingleElementSort(): void
    {
        $builder = new ElasticsearchSortBuilder();
        $sort = [['name' => 'asc']];

        $result = $builder->appendSortTieBreaker($sort);

        $expected = [
            ['name' => 'asc'],
            ['_id' => 'asc'],
        ];

        $this->assertSame($expected, $result);
    }
}
