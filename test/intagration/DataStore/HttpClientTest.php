<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\intagration\DataStore;

use Interop\Container\ContainerInterface;
use rollun\datastore\DataStore\HttpClient;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
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

    /**
     * Run "php -S localhost:9000 -t test/assets" in project root directory
     *
     * @throws \ReflectionException
     * @throws \rollun\datastore\DataStore\DataStoreException
     */
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
    }

    public function tearDown()
    {
        $this->mysqlManager->deleteTable($this->tableName);
    }

    /**
     * @var string
     */
    protected $filename;

    protected function createObject(): DataStoresInterface
    {
        $dataStoreService = 'dbDataStore';
        $url = getenv('HTTP_CLIENT_URL');
        $url .= "api/datastore/{$dataStoreService}";
        $client = new Client();

        return new HttpClient($client, $url);
    }
}
