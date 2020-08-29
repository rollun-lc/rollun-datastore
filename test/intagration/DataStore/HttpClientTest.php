<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\intagration\DataStore;

use Interop\Container\ContainerInterface;
use rollun\datastore\DataStore\DataStoreAbstract;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\HttpClient;
use rollun\datastore\TableGateway\TableManagerMysql;
use Zend\Http\Client;

class HttpClientTest extends BaseDataStoreTest
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
     * @var string
     */
    protected $tableName = 'testTable';

    protected $tableConfig = [
        DataStoreAbstract::DEF_ID => [
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

    /**
     * Run "php -S localhost:9000 -t test/public" in project root directory
     *
     * @throws DataStoreException
     */
    public function setUp()
    {
        parent::setUp();

        /** @var ContainerInterface $container */
        //$this->container = include './config/container.php';
        global $container;
        $this->container = $container;

        $adapter = $this->container->get('db');
        $this->mysqlManager = new TableManagerMysql($adapter);

        if ($this->mysqlManager->hasTable($this->tableName)) {
            $this->mysqlManager->deleteTable($this->tableName);
        }

        $this->mysqlManager->createTable($this->tableName, $this->tableConfig);
    }

    public function tearDown()
    {
        $this->mysqlManager->deleteTable($this->tableName);
    }

    /**
     * @var string
     */
    protected $filename;

    protected function createObject(): DataStoreAbstract
    {
        $dataStoreService = 'dbDataStore';
        $url = getenv('TEST_HOST') . "api/datastore/{$dataStoreService}";
        $client = new Client();

        return new HttpClient($client, $url);
    }

    public function testHeaderIdentifier()
    {
        $dataStoreService = 'testDataStore2';
        $url = getenv('TEST_HOST') . "api/datastore/{$dataStoreService}";
        $client = new Client();

        $object = new HttpClient($client, $url);
        $object->read(1);

        $this->assertEquals('test', $object->getIdentifier());
    }
}
