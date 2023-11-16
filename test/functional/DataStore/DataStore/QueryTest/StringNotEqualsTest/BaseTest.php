<?php

namespace rollun\test\functional\DataStore\DataStore\QueryTest\StringNotEqualsTest;

use rollun\datastore\DataStore\Interfaces\DataStoreInterface;
use rollun\test\functional\FunctionalTestCase;
use Xiag\Rql\Parser\Node\Query\LogicOperator\NotNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\EqNode;
use Xiag\Rql\Parser\Query;

abstract class BaseTest extends FunctionalTestCase
{
    protected const ID_NAME = 'id';
    protected const FIELD_NAME = 'field';
    protected const TABLE_NAME = 'string_not_equals_test';

    public function testGetNullValue(): void
    {
        $dataStore = $this->getDataStore();
        $dataStore->create($expectedRecord = [self::ID_NAME => 1, self::FIELD_NAME => null]);

        $query = new Query();
        $query->setQuery(new NotNode([new EqNode(self::FIELD_NAME, '123')]));
        $records = $dataStore->query($query);

        self::assertEquals([$expectedRecord], $records);
    }

    public function testGetNotMatchedValue(): void
    {
        $dataStore = $this->getDataStore();
        $dataStore->create($expectedRecord = [self::ID_NAME => 1, self::FIELD_NAME => 'foo']);

        $query = new Query();
        $query->setQuery(new NotNode([new EqNode(self::FIELD_NAME, 'bar')]));
        $records = $dataStore->query($query);

        self::assertEquals([$expectedRecord], $records);
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
