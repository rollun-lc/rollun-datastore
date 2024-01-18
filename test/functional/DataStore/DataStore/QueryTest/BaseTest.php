<?php

namespace rollun\test\functional\DataStore\DataStore\QueryTest;

use rollun\datastore\DataStore\Interfaces\DataStoreInterface;
use rollun\datastore\Rql\Node\ContainsNode;
use rollun\test\functional\FunctionalTestCase;
use Xiag\Rql\Parser\Node\AbstractQueryNode;
use Xiag\Rql\Parser\Node\Query\LogicOperator\NotNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\EqNode;
use Xiag\Rql\Parser\Query;

abstract class BaseTest extends FunctionalTestCase
{
    protected const ID_NAME = 'id';
    protected const FIELD_NAME = 'field';
    protected const TABLE_NAME = 'query_test';

    public function filterDataProvider(): array
    {
        return [
            // Test 'not(equal())' operator
            'Null value not equals to string' => [
                $expectedRecord = [self::ID_NAME => 1, self::FIELD_NAME => null], // initial record
                new NotNode([new EqNode(self::FIELD_NAME, '123')]), // query
                [$expectedRecord], // result
            ],
            'Get not equal string' => [
                $expectedRecord = [self::ID_NAME => 1, self::FIELD_NAME => 'foo'],
                new NotNode([new EqNode(self::FIELD_NAME, 'bar')]),
                [$expectedRecord],
            ],
            'String is not equals to itself' => [
                [self::ID_NAME => 1, self::FIELD_NAME => $value = uniqid()],
                new NotNode([new EqNode(self::FIELD_NAME, $value)]),
                [],
            ],

            // Test underscores in 'equal()' operator
            'Underscore not equals to null value' => [
                [self::ID_NAME => 1, self::FIELD_NAME => null],
                new EqNode(self::FIELD_NAME, '_'),
                [],
            ],
            'Underscore not equals to random string' => [
                [self::ID_NAME => 1, self::FIELD_NAME => uniqid()], // uniqid never returns exactly '_' string
                new EqNode(self::FIELD_NAME, '_'),
                [],
            ],
            'Underscore equals to itself' => [
                $expectedRecord = [self::ID_NAME => 1, self::FIELD_NAME => $value = '_'],
                new EqNode(self::FIELD_NAME, $value),
                [$expectedRecord],
            ],

            // Test underscore in 'contains()' operator
            'Null value not contains underscore' => [
                [self::ID_NAME => 1, self::FIELD_NAME => null],
                new ContainsNode(self::FIELD_NAME, '_'),
                [],
            ],
            'String not contains underscore' => [
                [self::ID_NAME => 1, self::FIELD_NAME => 'foo'],
                new ContainsNode(self::FIELD_NAME, '_'),
                [],
            ],
            'String contains underscore' => [
                $expectedRecord = [self::ID_NAME => 1, self::FIELD_NAME => $value = 'in_transit'],
                new EqNode(self::FIELD_NAME, $value),
                [$expectedRecord],
            ],
            'Underscore contains underscore' => [
                $expectedRecord = [self::ID_NAME => 1, self::FIELD_NAME => $value = '_'],
                new EqNode(self::FIELD_NAME, $value),
                [$expectedRecord],
            ],
        ];
    }

    /**
     * @dataProvider filterDataProvider
     */
    public function testFilter(array $record, AbstractQueryNode $queryNode, array $expectedRecords): void
    {
        $dataStore = $this->getDataStore();
        $dataStore->create($record);

        $query = new Query();
        $query->setQuery($queryNode);
        $records = $dataStore->query($query);

        self::assertEquals($expectedRecords, $records);
    }

    public function testDoNotGetMatchedValue(): void
    {
        $dataStore = $this->getDataStore();
        $dataStore->create([self::ID_NAME => 1, self::FIELD_NAME => $value = uniqid()]);

        $query = new Query();
        $query->setQuery(new NotNode([new EqNode(self::FIELD_NAME, $value)]));
        $records = $dataStore->query($query);

        self::assertEquals([], $records);
    }

    abstract protected function getDataStore(): DataStoreInterface;
}