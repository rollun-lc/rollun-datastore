<?php

namespace functional\DataStore\DataStore;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\TableGateway\TableGateway;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\DbTable;
use rollun\datastore\DataStore\Interfaces\DataStoreInterface;
use rollun\datastore\Rql\Node\BinaryNode\EqnNode;
use rollun\datastore\Rql\Node\ContainsNode;
use rollun\test\functional\FunctionalTestCase;
use Xiag\Rql\Parser\Node\AbstractQueryNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\EqNode;
use Xiag\Rql\Parser\Node\SelectNode;
use Xiag\Rql\Parser\Query;

class MysqlSetsTest extends FunctionalTestCase
{
    private const TABLE_NAME = 'sets_test';
    private const ID_FIELD = 'id';
    private const TAGS_FIELD = 'tags';

    public function queryDataProvider(): array
    {
        // Column of 'SET' type may sort values in different orders: record with value 'tire,liquid' can
        // be created with the value 'liquid,tire', so we compare result only by id field.
        return [
            'Equals in wrong order' => [
                [self::ID_FIELD => 1, self::TAGS_FIELD => 'liquid,screw,tire'], // record
                new EqNode(self::TAGS_FIELD, 'liquid,screw,tire'), // query
                null, // expected result
            ],
            'Equals in correct order' => [
                [self::ID_FIELD => 1, self::TAGS_FIELD => 'liquid,screw,tire'],
                new EqNode(self::TAGS_FIELD, 'tire,liquid,screw'),
                [self::ID_FIELD => 1],
            ],
            'Equals in different order' => [
                [self::ID_FIELD => 1, self::TAGS_FIELD => 'tire,liquid,screw'],
                new EqNode(self::TAGS_FIELD, 'liquid,screw,tire'),
                null,
            ],
            'Equals one of value' => [
                [self::ID_FIELD => 1, self::TAGS_FIELD => 'tire,liquid'],
                new EqNode(self::TAGS_FIELD, 'tire'),
                null,
            ],
            'Not empty column equals to empty string' => [
                [self::ID_FIELD => 1, self::TAGS_FIELD => 'liquid,tire'],
                new EqNode(self::TAGS_FIELD, ''),
                null,
            ],
            'Empty column equals to empty string' => [
                [self::ID_FIELD => 1, self::TAGS_FIELD => ''],
                new EqNode(self::TAGS_FIELD, ''),
                [self::ID_FIELD => 1],
            ],

            'Not empty column equals to null' => [
                [self::ID_FIELD => 1, self::TAGS_FIELD => 'liquid,tire'],
                new EqnNode(self::TAGS_FIELD),
                null,
            ],
            'Empty column equals to null' => [
                [self::ID_FIELD => 1, self::TAGS_FIELD => ''],
                new EqnNode(self::TAGS_FIELD),
                null,
            ],

            'Contains exactly same' => [
                [self::ID_FIELD => 1, self::TAGS_FIELD => $tags = 'tire,liquid,screw'],
                new ContainsNode(self::TAGS_FIELD, $tags),
                [self::ID_FIELD => 1],
            ],
            'Contains in different order' => [
                [self::ID_FIELD => 1, self::TAGS_FIELD => 'tire,liquid,screw'],
                new ContainsNode(self::TAGS_FIELD, 'liquid,screw,tire'),
                null,
            ],
            'Contains one of value' => [
                [self::ID_FIELD => 1, self::TAGS_FIELD => 'tire,liquid,screw'],
                new ContainsNode(self::TAGS_FIELD, 'tire'),
                [self::ID_FIELD => 1],
            ],
            'Contains first value with delimiter' => [
                [self::ID_FIELD => 1, self::TAGS_FIELD => 'tire,liquid,screw'],
                new ContainsNode(self::TAGS_FIELD, 'tire,'),
                [self::ID_FIELD => 1],
            ],
            'Contains last value with delimiter' => [
                [self::ID_FIELD => 1, self::TAGS_FIELD => 'tire,liquid,screw'],
                new ContainsNode(self::TAGS_FIELD, 'screw,'),
                null,
            ],
            'Not empty column contains empty string' => [
                [self::ID_FIELD => 1, self::TAGS_FIELD => 'liquid,tire'],
                new ContainsNode(self::TAGS_FIELD, ''),
                [self::ID_FIELD => 1],
            ],
            'Empty column contains empty string' => [
                [self::ID_FIELD => 1, self::TAGS_FIELD => ''],
                new ContainsNode(self::TAGS_FIELD, ''),
                [self::ID_FIELD => 1],
            ],
        ];
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testQuery(array $record, AbstractQueryNode $queryNode, ?array $expectedRecord): void
    {
        $dataStore = $this->getDataStore();
        $dataStore->create($record);

        $query = new Query();
        $query->setQuery($queryNode);
        $query->setSelect(new SelectNode([self::ID_FIELD]));
        $result = $dataStore->query($query);

        self::assertEquals($expectedRecord === null ? [] : [$expectedRecord], $result);
    }

    public function createDataProvider(): array
    {
        return [
            'All tags' => [
                [self::ID_FIELD => 1, self::TAGS_FIELD => 'tire,liquid,screw'], // record
                ['tire','liquid','screw'], // expected tags
            ],
            'All tags in another order' => [
                [self::ID_FIELD => 1, self::TAGS_FIELD => 'liquid,screw,tire'],
                ['tire','liquid','screw'],
            ],
            'Duplications is ignored' => [
                [self::ID_FIELD => 1, self::TAGS_FIELD => 'tire,tire,liquid,liquid,screw,screw'],
                ['tire','liquid','screw'],
            ],
            'Partial' => [
                [self::ID_FIELD => 1, self::TAGS_FIELD => 'screw,tire'],
                ['screw','tire'],
            ],
            'Empty' => [
                [self::ID_FIELD => 1, self::TAGS_FIELD => ''],
                [],
            ],
        ];
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(array $record, array $expectedTags): void
    {
        $created = $this->getDataStore()->create($record);

        $tags = $created[self::TAGS_FIELD] === '' ? [] : explode(',', $created[self::TAGS_FIELD]);
        self::assertEqualsCanonicalizing($expectedTags, $tags);
    }

    public function failDataProvider(): array
    {
        return [
            'Unsupported value' => [
                [self::ID_FIELD => 1, self::TAGS_FIELD => 'tire,liquid,screw,unsupported'],
            ],
            'Trailing comma' => [
                [self::ID_FIELD => 1, self::TAGS_FIELD => 'tire,liquid,screw,'],
            ],
        ];
    }

    /**
     * @dataProvider failDataProvider
     */
    public function testFail(array $record): void
    {
        $this->expectException(DataStoreException::class);

        $this->getDataStore()->create($record);
    }

    private function getDataStore(): DataStoreInterface
    {
        return $this->setUpDbTable();
    }

    private function setUpDbTable(): DbTable
    {
        $dbAdapter = $this->getDbAdapter();

        $sql = sprintf(<<<SQL
            create table if not exists %s
            (
                %s int                    not null,
                %s SET ('tire', 'liquid', 'screw') not null,
                constraint table_name_pk
                    primary key (id)
            );
            SQL, self::TABLE_NAME, self::ID_FIELD, self::TAGS_FIELD);

        $this->tearDownTable();
        $dbAdapter->driver->getConnection()->execute($sql);

        return new DbTable(new TableGateway(self::TABLE_NAME, $this->getDbAdapter()));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->tearDownTable();
    }

    private function tearDownTable(): void
    {
        $this->getDbAdapter()->driver->getConnection()->execute('DROP TABLE IF EXISTS ' . self::TABLE_NAME . ';');
    }

    private function getDbAdapter(): Adapter
    {
        return $this->getContainer()->get('db');
    }
}
