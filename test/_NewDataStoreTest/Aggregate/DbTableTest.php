<?php


namespace rollun\test\datastore\DataStore\Aggregate;

use rollun\datastore\DataStore\DbTable;
use rollun\datastore\TableGateway\TableManagerMysql;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\TableGateway\TableGateway;

class DbTableTest extends AbstractAggregateTest
{
    use AggregateSimpleDataProviderTrait;
    use AggregateMixedDataProviderTrait;
    /**
     * Prepare
     * @throws \ReflectionException
     */
    public function setUp()
    {
        parent::setUp();
        $tableName = "test_aggregate_simple_table";
        $adapter = $this->container->get("TestDbAdapter");

        //create db
        $tableManager = new TableManagerMysql($adapter, [
            TableManagerMysql::KEY_TABLES_CONFIGS => [
                $tableName => [
                    DbTable::DEF_ID => [
                        TableManagerMysql::FIELD_TYPE => "Integer",
                        TableManagerMysql::PRIMARY_KEY => true,
                        TableManagerMysql::FIELD_PARAMS => [
                            'nullable' => false
                        ]
                    ],
                ]
            ]
        ]);
        $tableManager->rewriteTable($tableName);

        //create store
        $tableGateway = new TableGateway($tableName, $adapter);
        $this->object = new DbTable($tableGateway);

        //create data
        foreach ($this->getDataProviderInitData() as $datum) {
            $this->object->create($datum);
        }
    }

    /**
     * @param string $filedName
     * @param $aggregateFunction
     * @return string
     */
    function decorateAggregateField($filedName, $aggregateFunction)
    {
        //return "{$aggregateFunction}({$filedName})";
        return "{$filedName}->{$aggregateFunction}";
    }

    /**
     * @return string
     */
    function getIdColumn()
    {
        return DbTable::DEF_ID;
    }
}