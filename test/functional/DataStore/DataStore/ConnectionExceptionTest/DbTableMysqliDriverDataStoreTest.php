<?php

namespace rollun\test\functional\DataStore\DataStore\ConnectionExceptionTest;

use Laminas\Db\TableGateway\TableGateway;
use rollun\datastore\DataStore\DbTable;

class DbTableMysqliDriverDataStoreTest extends BaseTest
{
    protected function getDataStore(): DbTable
    {
        return new DbTable(
            new TableGateway(
                table: 'pdo-connection-test',
                adapter: $this->getContainer()->get('db.mysqli.wrong-connection')
            )
        );
    }
}
