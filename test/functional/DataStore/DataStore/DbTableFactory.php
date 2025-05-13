<?php

namespace rollun\test\functional\DataStore\DataStore;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\TableGateway\TableGateway;
use rollun\datastore\DataStore\DbTable;
use rollun\datastore\TableGateway\TableManagerMysql;

final class DbTableFactory
{
    public function __construct(
        private AdapterInterface $adapter,
        private string $tableName,
        private array $tableConfig
    ) {}

    public function create(): DbTable
    {
        $this->reCreateTable();
        return new DbTable(
            new TableGateway($this->tableName, $this->adapter)
        );
    }

    public function delete(): void
    {
        $this->getMysqlManager()->deleteTable($this->tableName);
    }

    private function reCreateTable(): void
    {
        $mysqlManager = $this->getMysqlManager();

        if ($mysqlManager->hasTable($this->tableName)) {
            $mysqlManager->deleteTable($this->tableName);
        }

        $mysqlManager->createTable($this->tableName, $this->tableConfig);
    }

    private function getMysqlManager(): TableManagerMysql
    {
        return new TableManagerMysql($this->adapter);
    }
}
