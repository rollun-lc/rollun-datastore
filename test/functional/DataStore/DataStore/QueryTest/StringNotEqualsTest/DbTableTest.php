<?php

namespace rollun\test\functional\DataStore\DataStore\QueryTest\StringNotEqualsTest;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\TableGateway\TableGateway;
use rollun\datastore\DataStore\DbTable;
use rollun\datastore\DataStore\Interfaces\DataStoreInterface;
use rollun\datastore\TableGateway\TableManagerMysql;
use rollun\test\functional\DataStore\DataStore\DbTableFactory;

class DbTableTest extends BaseTest
{
    protected function getDataStore(): DataStoreInterface
    {
        return $this->getDbTableFactory()->create();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->getDbTableFactory()->delete();
    }

    private function getDbTableFactory(): DbTableFactory
    {
        return new DbTableFactory(
            adapter:  $this->getContainer()->get('db'),
            tableName: self::TABLE_NAME,
            tableConfig: [
                self::ID_NAME => [
                    'field_type' => TableManagerMysql::TYPE_INTEGER,
                ],
                self::FIELD_NAME => [
                    'field_type' => TableManagerMysql::TYPE_VARCHAR,
                    'field_params' => [
                        'length' => 255,
                        'nullable' => true
                    ]
                ],
            ],
        );
    }
}
