<?php

namespace rollun\test\functional\DataStore\DataStore\OperationTimedOutExceptionTest;

use Laminas\Db\TableGateway\TableGateway;
use rollun\datastore\DataStore\DbTable;
use rollun\datastore\TableGateway\SqlQueryBuilder;
use Xiag\Rql\Parser\Query;

class DbTableMysqliDriverDataStoreTest extends BaseTest
{
    protected function getDataStore(): DbTable
    {
        // Create DbTable but with sqlQueryBuilder that always will return sql with sleep for 2 seconds,
        // But with this method we can test only 'query' function, because other functions do not use sqlQueryBuilder
        return new class (
            new TableGateway(
                table: 'mysqli-timeout-test',
                adapter: $this->getContainer()->get('db.mysqli.timeout-1-sec')
            )
        ) extends DbTable {
            protected function getSqlQueryBuilder()
            {
                if ($this->sqlQueryBuilder == null) {
                    $this->sqlQueryBuilder = new class (
                        $this->dbTable->getAdapter(),
                        $this->dbTable->table
                    ) extends SqlQueryBuilder {
                        public function buildSql(Query $query): string
                        {
                            return 'SELECT SLEEP(2);';
                        }
                    };
                }

                return $this->sqlQueryBuilder;
            }
        };
    }

    public function testCreate(): void
    {
        $this->markTestSkipped('Current method to emulate timeout in Mysqli does not work for this function');
    }

    public function testMultiCreate(): void
    {
        $this->markTestSkipped('Current method to emulate timeout in Mysqli does not work for this function');
    }

    public function testUpdate(): void
    {
        $this->markTestSkipped('Current method to emulate timeout in Mysqli does not work for this function');
    }

    public function testMultiUpdate(): void
    {
        $this->markTestSkipped('Current method to emulate timeout in Mysqli does not work for this function');
    }

    public function testQueriedUpdate(): void
    {
        $this->markTestSkipped('Current method to emulate timeout in Mysqli does not work for this function');
    }

    public function testRewrite(): void
    {
        $this->markTestSkipped('Current method to emulate timeout in Mysqli does not work for this function');
    }

    public function testDelete(): void
    {
        $this->markTestSkipped('Current method to emulate timeout in Mysqli does not work for this function');
    }

    public function testQueriedDelete(): void
    {
        $this->markTestSkipped('Current method to emulate timeout in Mysqli does not work for this function');
    }

    public function testRead(): void
    {
        $this->markTestSkipped('Current method to emulate timeout in Mysqli does not work for this function');
    }

    public function testHas(): void
    {
        $this->markTestSkipped('Current method to emulate timeout in Mysqli does not work for this function');
    }
}
