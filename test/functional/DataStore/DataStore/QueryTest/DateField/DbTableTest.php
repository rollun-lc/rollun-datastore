<?php

namespace rollun\test\functional\DataStore\DataStore\QueryTest\DateField;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\TableGateway\TableGateway;
use rollun\datastore\DataStore\DbTable;
use rollun\datastore\DataStore\Interfaces\DataStoreInterface;
use rollun\datastore\TableGateway\TableManagerMysql;

class DbTableTest extends BaseTest
{
    /**
     * @var DbTable
     */
    private $dbTable;

    /**
     * @var TableManagerMysql
     */
    private $mysqlManager;


    protected function getDataStore(): DataStoreInterface
    {
        if ($this->dbTable === null) {
            $this->dbTable = $this->setUpDbTable();
        }
        return $this->dbTable;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->getMysqlManager()->deleteTable(self::TABLE_NAME);
    }

    private function setUpDbTable(): DbTable
    {
        $mysqlManager = $this->getMysqlManager();

        if ($mysqlManager->hasTable(self::TABLE_NAME)) {
            $mysqlManager->deleteTable(self::TABLE_NAME);
        }

        $mysqlManager->createTable(self::TABLE_NAME, [
            self::ID_NAME => [
                'field_type' => TableManagerMysql::TYPE_INTEGER,
            ],
            self::FIELD_NAME => [
                'field_type' => TableManagerMysql::TYPE_DATETIME,
            ],
        ]);
        $tableGateway = new TableGateway(self::TABLE_NAME, $this->getDbAdapter());

        return new DbTable($tableGateway);
    }

    private function getMysqlManager(): TableManagerMysql
    {
        if ($this->mysqlManager === null) {
            $this->mysqlManager = new TableManagerMysql($this->getDbAdapter());
        }
        return $this->mysqlManager;
    }

    private function getDbAdapter(): Adapter
    {
        return $this->getContainer()->get('db');
    }
}
