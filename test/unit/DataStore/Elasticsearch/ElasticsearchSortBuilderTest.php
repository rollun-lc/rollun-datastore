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
}
