<?php

namespace rollun\test\functional\DataStore\DataStore\QueryTest\Json;

use rollun\datastore\DataStore\Interfaces\DataStoreInterface;
use rollun\test\functional\FunctionalTestCase;
use Xiag\Rql\Parser\Node\Query\LogicOperator\NotNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\EqNode;
use Xiag\Rql\Parser\Query;

abstract class BaseTest extends FunctionalTestCase
{
    protected const ID_NAME = 'id';
    protected const FIELD_NAME = 'json_field';
    protected const TABLE_NAME = 'json_filters_test';

    public function testGetRecordsWithEmptyArray(): void
    {
        $dataStore = $this->getDataStore();
        $dataStore->create([self::ID_NAME => 1, self::FIELD_NAME => '{}']);
        $dataStore->create([self::ID_NAME => 2, self::FIELD_NAME => '["item-1", "item-2"]']);
        $dataStore->create($expectedRecord = [self::ID_NAME => 10, self::FIELD_NAME => '[]']);

        $query = new Query();
        $query->setQuery(new EqNode(self::FIELD_NAME, '[]'));
        $records = $dataStore->query($query);

        self::assertEquals([$expectedRecord], $records);
    }

    abstract protected function getDataStore(): DataStoreInterface;
}
