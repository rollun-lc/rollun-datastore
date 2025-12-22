<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\functional\DataStore\DataStore;

use InvalidArgumentException;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\DbTable;
use rollun\datastore\Rql\RqlQuery;
use rollun\datastore\TableGateway\TableManagerMysql;
use Xiag\Rql\Parser\Query;
use Laminas\Db\TableGateway\TableGateway;

class DbTableTest extends TestCase
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

    protected function setUp(): void
    {
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

    protected function tearDown(): void
    {
        $this->mysqlManager->deleteTable($this->tableName);
    }

    public function createObject($tableGateway = null, $writeLogs = false, $logger = null)
    {
        $tableGateway = $tableGateway ?: $this->tableGateway;

        if (!is_null($logger)) {
            return new DbTable($tableGateway, $writeLogs, $logger, null);
        }

        return new DbTable($tableGateway, $writeLogs);
    }

    public function testCreateSuccess()
    {
        $itemData = [
            'id' => 1,
            'name' => 'name',
            'surname' => 'surname',
        ];

        $object = $this->createObject();
        $object->create($itemData);
        $this->assertEquals($this->read($itemData['id']), $itemData);
    }

    public function testCreateFailWithItemExist()
    {
        $this->expectException(DataStoreException::class);

        $itemData = [
            'id' => 1,
            'name' => 'name',
            'surname' => 'surname',
        ];

        $this->create($itemData);
        $object = $this->createObject();
        $object->create($itemData);
    }

    public function testUpdateSuccess()
    {
        $itemData = [
            'id' => 1,
            'name' => 'name1',
            'surname' => 'surname1',
        ];

        $newItemData = [
            'id' => 1,
            'surname' => 'surname2',
        ];

        $this->create($itemData);
        $object = $this->createObject();
        $object->update($newItemData);
        $this->assertEquals([
            'id' => 1,
            'name' => 'name1',
            'surname' => 'surname2',
        ], $this->read($newItemData['id']));
    }

    public function testUpdateFailWithItemHasNotPrimaryKey()
    {
        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage('Item must has primary key');
        $object = $this->createObject();
        $object->update([
            'name' => 'name',
            'surname' => 'surname',
        ]);
    }

    public function testUpdateFailWithItemDoesNotExist()
    {
        $object = $this->createObject();

        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage(
            "[{$this->tableName}]Can't update item. [{$this->tableName}]Can't update item with id = 1"
        );

        $object->update([
            'id' => 1,
            'name' => 'name'
        ]);
    }

    public function testRead()
    {
        $itemData = [
            'id' => 1,
            'name' => 'name',
            'surname' => 'surname',
        ];
        $this->create($itemData);

        $object = $this->createObject();
        $this->assertEquals($itemData, $object->read(1));
    }

    public function testDelete()
    {
        $itemData = [
            'id' => 1,
            'name' => 'name',
            'surname' => 'surname',
        ];
        $this->create($itemData);

        $object = $this->createObject();
        $object->delete(1);
        $this->assertEquals($this->read($itemData['id']), null);
    }

    public function testDeleteAll()
    {
        $itemData1 = [
            'id' => 1,
            'name' => 'name1',
            'surname' => 'surname1',
        ];
        $itemData2 = [
            'id' => 2,
            'name' => 'name2',
            'surname' => 'surname2',
        ];
        $this->create($itemData1);
        $this->create($itemData2);

        $object = $this->createObject();
        $object->deleteAll();
        $this->assertEquals($this->read($itemData1['id']), null);
        $this->assertEquals($this->read($itemData2['id']), null);
    }

    public function testCount()
    {
        $itemData1 = [
            'id' => 1,
            'name' => 'name1',
            'surname' => 'surname1',
        ];
        $itemData2 = [
            'id' => 2,
            'name' => 'name2',
            'surname' => 'surname2',
        ];
        $itemData3 = [
            'id' => 3,
            'name' => 'name3',
            'surname' => 'surname3',
        ];
        $this->create($itemData1);
        $this->create($itemData2);

        $object = $this->createObject();
        $this->assertEquals(2, $object->count());

        $this->create($itemData3);
        $this->assertEquals(3, $object->count());
    }

    public function testQueriedDeleteSuccess()
    {
        $object = $this->createObject();

        foreach (range(1, 10) as $id) {
            $object->create([
                'id' => $id,
                'name' => "name{$id}",
                'surname' => "surname{$id}",
            ]);
        }

        $query = new RqlQuery('gt(id,3)');
        $object->queriedDelete($query);

        foreach (range(1, 10) as $id) {
            $this->assertEquals($id > 3, !$object->read($id));
        }
    }

    public function testQueriedUpdateSuccess()
    {
        $object = $this->createObject();

        foreach (range(1, 10) as $id) {
            $object->create([
                'id' => $id,
                'name' => "name{$id}",
                'surname' => "surname{$id}",
            ]);
        }

        $query = new RqlQuery('gt(id,3)&limit(100)');
        $ids = $object->queriedUpdate([
            'name' => "name0",
            'surname' => "surname0",
        ], $query);

        sort($ids);
        $this->assertEquals(range(4, 10), $ids);

        foreach (range(1, 10) as $id) {
            $row = $this->read($id);
            if ($id > 3) {
                $this->assertEquals(['id' => $id, 'name' => 'name0', 'surname' => 'surname0'], $row);
            } else {
                $this->assertEquals(['id' => $id, 'name' => "name{$id}", 'surname' => "surname{$id}"], $row);
            }
        }
    }

    public function testQueriedUpdateEmptyResult()
    {
        $object = $this->createObject();

        foreach (range(1, 5) as $id) {
            $object->create(['id' => $id, 'name' => "n{$id}", 'surname' => "s{$id}"]);
        }

        $query = new RqlQuery('gt(id,1000)&limit(100)');
        $ids = $object->queriedUpdate(['name' => 'X'], $query);

        $this->assertSame([], $ids);

        foreach (range(1, 5) as $id) {
            $this->assertEquals(['id' => $id, 'name' => "n{$id}", 'surname' => "s{$id}"], $this->read($id));
        }
    }

    public function queriedUpdateErrorsDataProvider()
    {
        return [
            'validationEmptyArray' => [
                InvalidArgumentException::class,
                'Expected non-empty associative array for update fields.',
                [],
                new RqlQuery('gt(id,1)'),
            ],
            'validationListInsteadOfAssociativeArray' => [
                InvalidArgumentException::class,
                'Expected non-empty associative array for update fields.',
                ['oops'],
                new RqlQuery('gt(id,1)'),
            ],
            'validationUnknownColumn' => [
                DataStoreException::class,
                null,
                ['unknown_col' => 123],
                new RqlQuery('gt(id,1)&limit(10)'),
            ],
            'validationRejectSelect' => [
                InvalidArgumentException::class,
                'Queried update does not support select or groupBy.',
                ['name' => 'x'],
                new RqlQuery('gt(id,1)&limit(1)&select(id)'),
            ],
            'validationRejectGroupBy' => [
                InvalidArgumentException::class,
                'Queried update does not support select or groupBy.',
                ['name' => 'x'],
                new RqlQuery('gt(id,1)&limit(1)&groupby(name)'),
            ],
            'validationBadFilter' => [
                DataStoreException::class,
                null,
                ['name' => 'X'],
                new RqlQuery('eq(unknown_field,1)&limit(10)'),
            ],
        ];
    }

    /**
     * @dataProvider queriedUpdateErrorsDataProvider
     */
    public function testQueriedUpdateErrorScenarios(
        string $exception,
        string|null $exceptionMessage,
        array $updateBody,
        RqlQuery $query,
    ) {
        $object = $this->createObject();

        foreach (range(1, 3) as $id) {
            $object->create(['id' => $id, 'name' => "n{$id}", 'surname' => "s{$id}"]);
        }

        $this->expectException($exception);
        if ($exceptionMessage) {
            $this->expectExceptionMessage($exceptionMessage);
        }

        try {
            $object->queriedUpdate($updateBody, $query);
        } finally {
            foreach (range(1, 3) as $id) {
                $this->assertEquals(['id' => $id, 'name' => "n{$id}", 'surname' => "s{$id}"], $this->read($id));
            }
        }
    }

    public function testWriteLog()
    {
        $loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $dataStore = $this->createObject(null, true, $loggerMock);

        // методы create, update, delete вызывают метод read, поэтому учитываем его логи тоже
        $loggerMock->expects($this->atLeast(7))
            ->method('debug')
            ->withConsecutive(
                [$this->isType('string'), Assert::containsEqual('insert')],
                [$this->isType('string'), Assert::containsEqual('read')],
                [$this->isType('string'), Assert::containsEqual('update')],
                [$this->isType('string'), Assert::containsEqual('read')],
                [$this->isType('string'), Assert::containsEqual('query')],
                [$this->isType('string'), Assert::containsEqual('read')],
                [$this->isType('string'), Assert::containsEqual('read')],
                [$this->isType('string'), Assert::containsEqual('delete')]
            );

        $dataStore->create([
            'id' => 1,
            'name' => "name",
            'surname' => "surname",
        ]);

        $dataStore->update([
            'id' => 1,
            'name' => "alter name",
            'surname' => "surname",
        ]);

        $dataStore->query(new Query());

        $dataStore->read(1);

        $dataStore->delete(1);
    }

    public function testWriteLogWhenException()
    {
        $loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $dbTableMock = $this->getMockBuilder(TableGateway::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dataStore = $this->createObject($dbTableMock, true, $loggerMock);

        $dbTableMock->method('getAdapter')
            ->willReturn($this->tableGateway->getAdapter());

        $dbTableMock->method('getTable')
            ->willReturn($this->tableGateway->getTable());

        $dbTableMock->method('insert')
            ->willThrowException(new \Exception());

        $dbTableMock->method('update')
            ->willThrowException(new \Exception());

        $dbTableMock->method('select')
            ->willThrowException(new \Exception());

        $dbTableMock->method('delete')
            ->willThrowException(new \Exception());

        // метод delete вызывает метод read, поэтому учитываем его логи тоже
        $loggerMock->expects($this->exactly(4))
            ->method('debug')
            ->withConsecutive(
                [$this->isType('string'), Assert::containsEqual('create')],
                [$this->isType('string'), Assert::containsEqual('update')],
                [$this->isType('string'), Assert::containsEqual('read')],
                [$this->isType('string'), Assert::containsEqual('read')],
                [$this->isType('string'), Assert::containsEqual('delete')]
            );

        try {
            $dataStore->create([
                'id' => 1,
                'name' => "name",
                'surname' => "surname",
            ]);
        } catch (\Exception $e) {}

        try {
            $dataStore->update([
                'id' => 1,
                'name' => "alter name",
                'surname' => "surname",
            ]);
        } catch (\Exception $e) {}

        try {
            $dataStore->read(1);
        } catch (\Exception $e) {}

        try {
            $dataStore->delete(1);
        } catch (\Exception $e) {}
    }

    public function testNotWriteLogsWhenDisabled()
    {
        $loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $dataStore = $this->createObject(null, false, $loggerMock);

        $loggerMock->expects($this->never())
            ->method('debug');

        $dataStore->create([
            'id' => 1,
            'name' => "name",
            'surname' => "surname",
        ]);

        $dataStore->update([
            'id' => 1,
            'name' => "alter name",
            'surname' => "surname",
        ]);

        $dataStore->query(new Query());

        $dataStore->read(1);

        $dataStore->delete(1);
    }

    /**
     * Read record by id directly through TableGateway
     *
     * @param $id
     * @return null
     */
    protected function read($id)
    {
        $resultSet = $this->tableGateway->select(['id' => $id]);
        $result = $resultSet->toArray();

        if (count($result)) {
            return $result[0];
        }

        return null;
    }

    /**
     * Create record through TableGateway
     *
     * @param $itemData
     */
    protected function create($itemData)
    {
        $this->tableGateway->insert($itemData);
    }
}
