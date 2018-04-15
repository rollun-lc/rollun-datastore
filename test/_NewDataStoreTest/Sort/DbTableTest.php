<?php


namespace rollun\test\datastore\DataStore\Sort;

use rollun\datastore\DataStore\DbTable;
use rollun\datastore\TableGateway\TableManagerMysql;
use rollun\test\datastore\DataStore\AbstractDbTableTest;
use rollun\test\datastore\DataStore\NewStyleAggregateDecoratorTrait;
use rollun\test\datastore\DataStore\OldStyleAggregateDecoratorTrait;
use Zend\Db\Adapter\Adapter;
use Zend\Db\TableGateway\TableGateway;

class DbTableTest extends AbstractDbTableTest
{
    use SortTestTrait;
}