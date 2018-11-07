<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\functional\DataStore\DataStore;

use PHPUnit\Framework\TestCase;
use rollun\datastore\DataStore\SerializedDbTable;
use rollun\datastore\TableGateway\SqlQueryBuilder;
use rollun\datastore\TableGateway\TableManagerMysql;
use rollun\dic\InsideConstruct;
use Zend\Db\TableGateway\TableGateway;
use Zend\ServiceManager\ServiceManager;

class SerializedDbTableTest extends TestCase
{
    /**
     * @var TableManagerMysql
     */
    protected $mysqlManager;

    /**
     * @var ServiceManager
     */
    protected $container;

    /**
     * @var SerializedDbTable
     */
    protected $object;

    /**
     * @var string
     */
    protected $tableName = 'testTable';

    protected $tableConfig = [
        'id' => [
            'field_type' => 'Integer',
        ],
        'name' => [
            'field_type' => 'Varchar',
            'field_params' => [
                'length' => 255,
            ]
        ],
        'surname' => [
            'field_type' => 'Varchar',
            'field_params' => [
                'length' => 255,
            ]
        ],
    ];
    public function setUp()
    {
        /** @var ServiceManager $container */
        $this->container = include './config/container.php';

        $adapter = $container->get('db');
        $this->mysqlManager = new TableManagerMysql($adapter);

        if ($this->mysqlManager->hasTable($this->tableName)) {
            $this->mysqlManager->deleteTable($this->tableName);
        }

        $this->mysqlManager->createTable($this->tableName, $this->tableConfig);

        $tableGateway = new TableGateway($this->tableName, $adapter);
        $sqlQueryBuilder = new SqlQueryBuilder($container->get('db'), $this->tableName);

        $container = clone $this->container;
        $container->setService($this->tableName, $tableGateway);
        $container->setService(SqlQueryBuilder::class, $sqlQueryBuilder);
        InsideConstruct::setContainer($container);

        $this->object = new SerializedDbTable($tableGateway, $sqlQueryBuilder);
    }

    public function tearDown()
    {
        $this->mysqlManager->deleteTable($this->tableName);
        InsideConstruct::setContainer($this->container);
    }

    public function testSerializable()
    {
        $this->assertEquals($this->object, unserialize(serialize($this->object)));
    }
}
