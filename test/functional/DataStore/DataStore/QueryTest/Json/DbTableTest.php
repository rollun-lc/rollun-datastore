<?php

namespace rollun\test\functional\DataStore\DataStore\QueryTest\Json;

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

    public function testGetRecordsWithEmptyArray(): void
    {
        $this->markTestSkipped('Issue not yet fixed');
    }

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

        $dbAdapter = $this->getDbAdapter();

        $sql = sprintf(<<<SQL
                    CREATE TABLE %s (
                        %s INT NOT NULL PRIMARY KEY,
                        %s JSON NOT NULL
                    );
                    SQL, self::TABLE_NAME, self::ID_NAME, self::FIELD_NAME);
        $dbAdapter->query($sql, Adapter::QUERY_MODE_EXECUTE);

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
