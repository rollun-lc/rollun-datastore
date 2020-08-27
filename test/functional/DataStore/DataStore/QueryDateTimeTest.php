<?php


namespace rollun\test\intagration\DataStore;


use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use rollun\datastore\DataStore\DataStoreAbstract;
use rollun\datastore\DataStore\DbTable;
use rollun\datastore\DataStore\HttpClient;
use rollun\datastore\DataStore\Memory;
use rollun\datastore\TableGateway\SqlQueryBuilder;
use rollun\datastore\TableGateway\TableManagerMysql;
use Xiag\Rql\Parser\Node\Query\ArrayOperator\InNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\EqNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\GtNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\LtNode;
use Xiag\Rql\Parser\Query;
use Zend\Db\TableGateway\TableGateway;
use Zend\Http\Client;

class QueryDateTimeTest extends TestCase
{
    protected $container;

    protected $mysqlManager;

    protected $tableName = 'testTable';

    /**
     * @var TableGateway
     */
    protected $tableGateway;

    protected $tableConfig = [
        'id' => [
            'field_type' => 'Integer',
        ],
        'created_at' => [
            'field_type' => 'Datetime',
        ],
    ];

    public function setUp()
    {
        global $container;

        $this->container = $container;

        $adapter = $this->container->get('db');
        $this->mysqlManager = new TableManagerMysql($adapter);

        if ($this->mysqlManager->hasTable($this->tableName)) {
            $this->mysqlManager->deleteTable($this->tableName);
        }

        $this->mysqlManager->createTable($this->tableName, $this->tableConfig);
        $this->tableGateway = new TableGateway($this->tableName, $adapter);

        for ($i = 1; $i <= 5; $i++) {
            $this->tableGateway->insert([
                'id' => $i,
                'created_at' => "2020-01-01 00:0{$i}:12",
            ]);
        }
    }

    public function tearDown()
    {
        $this->mysqlManager->deleteTable($this->tableName);
    }

    public function testDbTableGreaterThan()
    {
        $dataStore = new DbTable($this->tableGateway);

        $query = new Query();

        $date = '2020-01-01 00:04';

        $query->setQuery(new GtNode('created_at', $date));
        $result = $dataStore->query($query);

        $this->assertCount(2, $result);
        $this->assertEquals([4, 5],array_column($result, 'id') );
    }

    public function testDbTableLessThan()
    {
        $dataStore = new DbTable($this->tableGateway);

        $query = new Query();

        $date = '2020-01-01 00:04';

        $query->setQuery(new LtNode('created_at', $date));
        $result = $dataStore->query($query);

        $this->assertCount(3, $result);
        $this->assertEquals([1, 2, 3], array_column($result, 'id'));
    }

    public function testDbTableEqual()
    {
        $dataStore = new DbTable($this->tableGateway);
        $query = new Query();

        $date = '2020-01-01 00:04.12';

        $query->setQuery(new EqNode('created_at', $date));
        $result = $dataStore->query($query);

        $this->assertCount(1, $result);
        $this->assertEquals(4, $result[0]['id']);
    }

    public function testHttpClientGreatThan()
    {
        $dataStore = $this->createHttpDataStore();
        $query = new Query();

        $date = '2020-01-01 00:04';

        $query->setQuery(new GtNode('created_at', $date));
        $result = $dataStore->query($query);

        $this->assertCount(2, $result);
        $this->assertEquals([4, 5],array_column($result, 'id') );
    }

    public function testHttpClientLessThan()
    {
        $dataStore = $this->createHttpDataStore();
        $query = new Query();

        $date = '2020-01-01 00:04';

        $query->setQuery(new LtNode('created_at', $date));
        $result = $dataStore->query($query);

        $this->assertCount(3, $result);
        $this->assertEquals([1, 2, 3], array_column($result, 'id'));
    }

    public function testDbTableIsoFormatGreatThan()
    {
        $dataStore = new DbTable($this->tableGateway);

        $query = new Query();

        $date = '2020-01-01T00:04:00';

        $query->setQuery(new GtNode('created_at', $date));
        $result = $dataStore->query($query);

        $this->assertCount(2, $result);
        $this->assertEquals([4, 5],array_column($result, 'id') );
    }

    public function testHttpClientIsoFormatGreatThan()
    {
        $dataStore = $this->createHttpDataStore();

        $query = new Query();

        $date = '2020-01-01T00:04:00';

        $query->setQuery(new GtNode('created_at', $date));
        $result = $dataStore->query($query);

        $this->assertCount(2, $result);
        $this->assertEquals([4, 5],array_column($result, 'id') );
    }

    public function testDbTableIsoFormatEqual()
    {
        $dataStore = new DbTable($this->tableGateway);
        $query = new Query();

        $date = '2020-01-01T00:04.12';

        $query->setQuery(new EqNode('created_at', $date));
        $result = $dataStore->query($query);

        $this->assertCount(1, $result);
        $this->assertEquals(4, $result[0]['id']);
    }

    public function testHttpClientIsoFormatEqual()
    {
        $dataStore = $this->createHttpDataStore();
        $query = new Query();

        $date = '2020-01-01T00:04.12';

        $query->setQuery(new EqNode('created_at', $date));
        $result = $dataStore->query($query);

        $this->assertCount(1, $result);
        $this->assertEquals(4, $result[0]['id']);
    }

    public function testDbTableIn()
    {
        $dataStore = new DbTable($this->tableGateway);
        $query = new Query();

        $date = '2020-01-01T00:04.12';

        $query->setQuery(new InNode('created_at', [
            $date
        ]));
        $result = $dataStore->query($query);

        $this->assertCount(1, $result);
        $this->assertEquals(4, $result[0]['id']);
    }

    public function testHttpClientIn()
    {
        $dataStore = $this->createHttpDataStore();
        $query = new Query();

        $date = '2020-01-01T00:04.12';

        $query->setQuery(new InNode('created_at', [
            $date
        ]));
        $result = $dataStore->query($query);

        $this->assertCount(1, $result);
        $this->assertEquals(4, $result[0]['id']);
    }

    public function testDateTime()
    {
        $dataStore = new DbTable($this->tableGateway);
        $query = new Query();

        $date = '2020-01-01T00:04.12';

        $query->setQuery(new EqNode('created_at', new \DateTime($date)));
        $result = $dataStore->query($query);

        $this->assertCount(1, $result);
        $this->assertEquals(4, $result[0]['id']);
    }

    /**
     * @todo
     */
    /*public function testMemoryGreatThan()
    {
        $dataStore = new Memory();
        for ($i = 1; $i <= 5; $i++) {
            $dataStore->create([
                'id' => $i,
                'created_at' => new \DateTime("2020-01-01T00:0{$i}"),
            ]);
        }

        $query = new Query();

        $date = '2020-01-01 00:04';

        $query->setQuery(new GtNode('created_at', new \DateTime($date)));
        $result = $dataStore->query($query);

        $this->assertCount(2, $result);
        $this->assertEquals([4, 5],array_column($result, 'id') );
    }*/

    protected function createHttpDataStore(): DataStoreAbstract
    {
        $dataStoreService = 'dbDataStore';
        $url = getenv('TEST_HOST') . "api/datastore/{$dataStoreService}";
        $client = new Client();

        return new HttpClient($client, $url);
    }
}