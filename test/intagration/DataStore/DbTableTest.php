<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\intagration\DataStore;

use Interop\Container\ContainerInterface;
use rollun\datastore\DataStore\DbTable;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use rollun\datastore\TableGateway\SqlQueryBuilder;
use rollun\datastore\TableGateway\TableManagerMysql;
use Zend\Db\TableGateway\TableGateway;

class DbTableTest extends BaseDataStoreTest
{
    /**
     * @var TableManagerMysql
     */
    protected $mysqlManager;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var TableGateway
     */
    protected $tableGateway;

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
        parent::setUp();

        /** @var ContainerInterface $container */
        $this->container = include './config/container.php';
        $adapter = $container->get('db');
        $this->mysqlManager = new TableManagerMysql($adapter);

        if ($this->mysqlManager->hasTable($this->tableName)) {
            $this->mysqlManager->deleteTable($this->tableName);
        }

        $this->mysqlManager->createTable($this->tableName, $this->tableConfig);
        $this->tableGateway = new TableGateway($this->tableName, $adapter);
    }

    public function tearDown()
    {
        $this->mysqlManager->deleteTable($this->tableName);
    }

    public function createObject(): DataStoresInterface
    {
        $adapter = $this->container->get('db');
        $sqlQueryBuilder = new SqlQueryBuilder($adapter, $this->tableName);

        return new DbTable($this->tableGateway, $sqlQueryBuilder);
    }
}
