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
                'nullable' => true,
            ],
        ],
        'surname' => [
            'field_type' => 'Varchar',
            'field_params' => [
                'length' => 255,
                'nullable' => true,
            ],
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
            'name' => 'name',
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

    public function testMultiUpdateSuccess()
    {
        $object = $this->createObject();

        // Create initial records
        $initialRecords = [
            ['id' => 1, 'name' => 'name1', 'surname' => 'surname1'],
            ['id' => 2, 'name' => 'name2', 'surname' => 'surname2'],
            ['id' => 3, 'name' => 'name3', 'surname' => 'surname3'],
        ];

        foreach ($initialRecords as $record) {
            $object->create($record);
        }

        // Update multiple records
        $updateRecords = [
            ['id' => 1, 'name' => 'updated1', 'surname' => 'updated_surname1'],
            ['id' => 2, 'name' => 'updated2'],
            ['id' => 3, 'surname' => 'updated_surname3'],
        ];

        $ids = $object->multiUpdate($updateRecords);

        sort($ids);
        $this->assertEquals([1, 2, 3], $ids);

        // Verify updates
        $this->assertEquals(
            ['id' => 1, 'name' => 'updated1', 'surname' => 'updated_surname1'],
            $this->read(1)
        );
        $this->assertEquals(
            ['id' => 2, 'name' => 'updated2', 'surname' => 'surname2'],
            $this->read(2)
        );
        $this->assertEquals(
            ['id' => 3, 'name' => 'name3', 'surname' => 'updated_surname3'],
            $this->read(3)
        );
    }

    public function testMultiUpdateThrowsExceptionForSingleRecord()
    {
        $object = $this->createObject();

        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage('Item id must be an array, integer given');

        // Pass single record instead of array of records
        $object->multiUpdate(['id' => 1, 'name' => 'test']);
    }

    public function testMultiUpdateThrowsExceptionForInvalidRecord()
    {
        $object = $this->createObject();

        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage('Item 2 must be an array, string given');

        // Pass array with non-array element
        $object->multiUpdate([
            ['id' => 1, 'name' => 'valid'],
            'invalid_record',
        ]);
    }

    /**
     * @dataProvider multiUpdateEdgeCasesProvider
     */
    public function testMultiUpdateEdgeCases(
        array $initialRecords,
        array $updateRecords,
        array $expectedIds,
        array $verifications,
        string $description
    ) {
        $object = $this->createObject();

        // Create initial records
        foreach ($initialRecords as $record) {
            $object->create($record);
        }

        // Execute multiUpdate
        $ids = $object->multiUpdate($updateRecords);
        sort($ids);

        // Verify returned IDs
        $this->assertEquals($expectedIds, $ids, $description);

        // Verify record states
        foreach ($verifications as $id => $expectedRecord) {
            $this->assertEquals($expectedRecord, $this->read($id), "Failed for record ID {$id}: {$description}");
        }
    }

    public function multiUpdateEdgeCasesProvider(): array
    {
        return [
            'update single column' => [
                'initialRecords' => [
                    ['id' => 1, 'name' => 'name1', 'surname' => 'surname1'],
                    ['id' => 2, 'name' => 'name2', 'surname' => 'surname2'],
                ],
                'updateRecords' => [
                    ['id' => 1, 'name' => 'updated1'],
                    ['id' => 2, 'surname' => 'updated_surname2'],
                ],
                'expectedIds' => [1, 2],
                'verifications' => [
                    1 => ['id' => 1, 'name' => 'updated1', 'surname' => 'surname1'],
                    2 => ['id' => 2, 'name' => 'name2', 'surname' => 'updated_surname2'],
                ],
                'description' => 'Each record updates only its specified columns',
            ],
            'update with different columns per record' => [
                'initialRecords' => [
                    ['id' => 1, 'name' => 'name1', 'surname' => 'surname1'],
                    ['id' => 2, 'name' => 'name2', 'surname' => 'surname2'],
                    ['id' => 3, 'name' => 'name3', 'surname' => 'surname3'],
                ],
                'updateRecords' => [
                    ['id' => 1, 'name' => 'new1'],
                    ['id' => 2, 'surname' => 'new2'],
                    ['id' => 3, 'name' => 'new3', 'surname' => 'new3s'],
                ],
                'expectedIds' => [1, 2, 3],
                'verifications' => [
                    1 => ['id' => 1, 'name' => 'new1', 'surname' => 'surname1'],
                    2 => ['id' => 2, 'name' => 'name2', 'surname' => 'new2'],
                    3 => ['id' => 3, 'name' => 'new3', 'surname' => 'new3s'],
                ],
                'description' => 'Partial updates work correctly with varying columns',
            ],
        ];
    }

    public function testMultiUpdateRollsBackOnFailure()
    {
        $object = $this->createObject();

        $initialRecords = [
            ['id' => 1, 'name' => 'name1', 'surname' => 'surname1'],
            ['id' => 2, 'name' => 'name2', 'surname' => 'surname2'],
        ];

        foreach ($initialRecords as $record) {
            $object->create($record);
        }

        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage("Can't multi update records");

        try {
            $object->multiUpdate([
                ['id' => 1, 'name' => 'updated1'],
                // Unknown column should make UPDATE fail and trigger rollback
                ['id' => 2, 'unknown_field' => 'boom'],
            ]);
        } finally {
            $this->assertEquals($initialRecords[0], $this->read(1));
            $this->assertEquals($initialRecords[1], $this->read(2));
        }
    }

    public function testMultiUpdateWithNullValues()
    {
        $object = $this->createObject();

        // Create initial records
        $object->create(['id' => 1, 'name' => 'name1', 'surname' => 'surname1']);
        $object->create(['id' => 2, 'name' => 'name2', 'surname' => 'surname2']);

        // Update with NULL values
        $ids = $object->multiUpdate([
            ['id' => 1, 'name' => null],
            ['id' => 2, 'surname' => null],
        ]);

        sort($ids);
        $this->assertEquals([1, 2], $ids);

        // Verify NULL was set correctly
        $this->assertEquals(['id' => 1, 'name' => null, 'surname' => 'surname1'], $this->read(1));
        $this->assertEquals(['id' => 2, 'name' => 'name2', 'surname' => null], $this->read(2));
    }

    public function testMultiUpdateThrowsExceptionForDuplicateIds()
    {
        $object = $this->createObject();

        $object->create(['id' => 1, 'name' => 'name1', 'surname' => 'surname1']);

        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage("Duplicate primary key '1' found in multiUpdate input");

        $object->multiUpdate([
            ['id' => 1, 'name' => 'first'],
            ['id' => 1, 'name' => 'duplicate'],
        ]);
    }

    public function testMultiUpdateThrowsExceptionForNonExistentRecords()
    {
        $object = $this->createObject();

        $initialRecords = [
            ['id' => 1, 'name' => 'name1', 'surname' => 'surname1'],
            ['id' => 2, 'name' => 'name2', 'surname' => 'surname2'],
        ];

        foreach ($initialRecords as $record) {
            $object->create($record);
        }

        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage("Can't update items with ids: 999");

        try {
            $object->multiUpdate([
                ['id' => 1, 'name' => 'updated1'],
                ['id' => 999, 'name' => 'non_existent'],
            ]);
        } finally {
            // Verify rollback - original records unchanged
            $this->assertEquals($initialRecords[0], $this->read(1));
            $this->assertEquals($initialRecords[1], $this->read(2));
        }
    }

    public function testMultiUpdateThrowsExceptionForRecordWithoutPrimaryKey()
    {
        $object = $this->createObject();

        $object->create(['id' => 1, 'name' => 'name1', 'surname' => 'surname1']);

        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage('Item 2 must have primary key');

        $object->multiUpdate([
            ['id' => 1, 'name' => 'valid'],
            ['name' => 'no_id', 'surname' => 'missing_pk'],
        ]);
    }

    public function testMultiUpdateThrowsExceptionForEmptyArray()
    {
        $object = $this->createObject();

        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage('Collection of arrays expected for multiUpdate');

        $object->multiUpdate([]);
    }

    public function testMultiUpdateThrowsExceptionWhenNoColumnsToUpdate()
    {
        $object = $this->createObject();

        $object->create(['id' => 1, 'name' => 'name1', 'surname' => 'surname1']);

        $this->expectException(DataStoreException::class);
        // The early guard rejects PK-only input before any SQL is built,
        // producing a specific message instead of a server-side syntax error
        // surfaced via the generic "Can't multi update records" wrapper.
        $this->expectExceptionMessageMatches('/No columns to update/');

        try {
            $object->multiUpdate([
                ['id' => 1],
            ]);
        } finally {
            // Record should remain unchanged when update fails
            $this->assertEquals(['id' => 1, 'name' => 'name1', 'surname' => 'surname1'], $this->read(1));
        }
    }

    /**
     * Recreate the test table with a VARCHAR primary key named `sku`.
     * Used by the multiUpdate regression tests below — the default fixture
     * has an INT PK which cannot reproduce the numeric-prefix sibling bug.
     */
    private function recreateTableWithVarcharPk(): TableGateway
    {
        $config = [
            'sku' => [
                'field_type' => 'Varchar',
                'field_params' => [
                    'length' => 64,
                    'nullable' => false,
                ],
                'field_primary_key' => true,
            ],
            'name' => [
                'field_type' => 'Varchar',
                'field_params' => [
                    'length' => 255,
                    'nullable' => true,
                ],
            ],
        ];

        if ($this->mysqlManager->hasTable($this->tableName)) {
            $this->mysqlManager->deleteTable($this->tableName);
        }
        $this->mysqlManager->createTable($this->tableName, $config);

        $adapter = $this->container->get('db');
        $this->tableGateway = new TableGateway($this->tableName, $adapter);
        return $this->tableGateway;
    }

    /**
     * Read a row by its varchar `sku` PK directly through TableGateway.
     */
    private function readBySku(string $sku): ?array
    {
        $resultSet = $this->tableGateway->select(['sku' => $sku]);
        $result = $resultSet->toArray();
        return count($result) ? $result[0] : null;
    }

    /**
     * Canonical regression for the multiUpdate sibling-row bug.
     *
     * Before the fix: PHP coerced the canonical numeric-string array key
     * '49956' to int(49956); the int reached the IN-clause unquoted; MySQL
     * implicit-cast the varchar column and matched both '49956' and '49956-2';
     * the build loop then crashed reading $recordsMap['49956-2'] which did
     * not exist.
     *
     * After the fix: $ids preserves the caller's string type, the IN-clause
     * is rendered quoted, MySQL does an exact string compare, and only the
     * intended row is updated.
     */
    public function testMultiUpdateOnVarcharPkDoesNotTouchNumericPrefixSibling(): void
    {
        $this->recreateTableWithVarcharPk();
        $this->tableGateway->insert(['sku' => '49956',   'name' => 'orig-A']);
        $this->tableGateway->insert(['sku' => '49956-2', 'name' => 'orig-B']);

        $object = new DbTable($this->tableGateway, false, null, 'sku');
        $ids = $object->multiUpdate([
            ['sku' => '49956', 'name' => 'updated-A'],
        ]);

        $this->assertSame(['49956'], $ids);
        $this->assertSame('updated-A', $this->readBySku('49956')['name']);
        $this->assertSame('orig-B', $this->readBySku('49956-2')['name']);
    }

    /**
     * Locks in the count-invariant guard introduced as part of the fix.
     *
     * If a caller passes an integer for a varchar PK while a numeric-prefix
     * sibling row exists, the SqlConditionBuilder still renders the int
     * unquoted (the int-passthrough in prepareFieldValue is intentionally
     * left untouched in this hotfix to avoid a wider blast radius). MySQL
     * then matches the sibling. multiUpdate must detect the phantom and
     * fail loud, rolling back the transaction without touching either row.
     *
     * If a future "optimization" drops the count check this test catches it.
     */
    public function testMultiUpdateRollsBackWhenIntCallerCausesPhantomMatch(): void
    {
        $this->recreateTableWithVarcharPk();
        $this->tableGateway->insert(['sku' => '49956',   'name' => 'orig-A']);
        $this->tableGateway->insert(['sku' => '49956-2', 'name' => 'orig-B']);

        $object = new DbTable($this->tableGateway, false, null, 'sku');

        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessageMatches('/Lock-select returned more rows/');

        try {
            $object->multiUpdate([
                ['sku' => 49956, 'name' => 'should-not-apply'],
            ]);
        } finally {
            $this->assertSame('orig-A', $this->readBySku('49956')['name']);
            $this->assertSame('orig-B', $this->readBySku('49956-2')['name']);
        }
    }

    /**
     * Non-canonical numeric-string PKs (leading zero, etc.) were never
     * affected by PHP's int-coercion of array keys — '00100' stays as a
     * string key. The fix reshuffled how $ids is collected; this test
     * confirms it did not accidentally regress that path.
     */
    public function testMultiUpdateOnVarcharPkPreservesLeadingZeroId(): void
    {
        $this->recreateTableWithVarcharPk();
        $this->tableGateway->insert(['sku' => '00100', 'name' => 'orig']);

        $object = new DbTable($this->tableGateway, false, null, 'sku');
        $ids = $object->multiUpdate([
            ['sku' => '00100', 'name' => 'updated'],
        ]);

        $this->assertSame(['00100'], $ids);
        $this->assertSame('updated', $this->readBySku('00100')['name']);
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
        } catch (\Exception) {
        }

        try {
            $dataStore->update([
                'id' => 1,
                'name' => "alter name",
                'surname' => "surname",
            ]);
        } catch (\Exception) {
        }

        try {
            $dataStore->read(1);
        } catch (\Exception) {
        }

        try {
            $dataStore->delete(1);
        } catch (\Exception) {
        }
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
