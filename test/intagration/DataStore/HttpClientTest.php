<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\intagration\DataStore;

use Psr\Container\ContainerInterface;
use rollun\datastore\DataStore\DataStoreAbstract;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\HttpClient;
use rollun\datastore\Rql\RqlQuery;
use rollun\datastore\TableGateway\TableManagerMysql;
use Laminas\Http\Client;

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
    protected function setUp(): void
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

    protected function tearDown(): void
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

    public function testQueriedUpdateSuccess()
    {
        $this->markTestIncomplete('Atm queried update handler functional is stubbed for test');
    }

    public function testDataIntegritySuccess()
    {
        $object = $this->createObject();
        $object->queriedDelete(new RqlQuery());
        $range13 = range(1, 3);

        foreach ($range13 as $id) {
            $object->create([
                $object->getIdentifier() => $id,
                'name' => 'name',
                'surname' => 'surname',
            ]);
        }

        $this->assertEquals($object->count(), count($range13));
        $range49 = range(4, 9);

        foreach ($range49 as $id) {
            $object->create([
                $object->getIdentifier() => $id,
                'name' => 'name',
                'surname' => 'surname',
            ]);
        }

        $this->assertEquals($object->count(), count($range13) + count($range49));

        $object->delete(2);
        $object->delete(6);
        $this->assertEquals($object->count(), (count($range13) + count($range49)) - 2);


        $object->create([
            $object->getIdentifier() => 10,
            'name' => 'name',
            'surname' => 'surname',
        ]);
        $this->assertEquals($object->count(), (count($range13) + count($range49)) - 1);

        $object->create([
            $object->getIdentifier() => 2,
            'name' => 'name',
            'surname' => 'surname',
        ]);

        $object->create([
            $object->getIdentifier() => 6,
            'name' => 'name',
            'surname' => 'surname',
        ]);
        $this->assertEquals($object->count(), (count($range13) + count($range49)) + 1);

        $object->queriedDelete(new RqlQuery());
        $this->assertEquals(0, $object->count());
    }
}
