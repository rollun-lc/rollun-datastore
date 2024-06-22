<?php

namespace rollun\test\functional\DataStore\DataStore\ConnectionExceptionTest;

use rollun\datastore\DataStore\ConnectionException;
use rollun\datastore\DataStore\Interfaces\DataStoreInterface;
use rollun\test\functional\FunctionalTestCase;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\EqNode;
use Xiag\Rql\Parser\Query;

abstract class BaseTest extends FunctionalTestCase
{
    /**
     * Should return dataStore that have problems with connection
     */
    abstract protected function getDbTableDataStore(): DataStoreInterface;

    public function testCreate(): void
    {
        $this->expectException(ConnectionException::class);

        $this->getDbTableDataStore()->create(['id' => 1, 'value' => 'foo']);
    }

    public function testMultiCreate(): void
    {
        $this->expectException(ConnectionException::class);

        $this->getDbTableDataStore()->multiCreate([
            ['id' => 1, 'value' => 'foo'],
            ['id' => 2, 'value' => 'bar'],
        ]);
    }

    public function testUpdate(): void
    {
        $this->expectException(ConnectionException::class);

        $this->getDbTableDataStore()->update(['id' => 1, 'value' => 'foo']);
    }

    public function testMultiUpdate(): void
    {
        $updatedRecords = $this->getDbTableDataStore()->multiUpdate([
            ['id' => 1, 'value' => 'foo'],
            ['id' => 2, 'value' => 'bar'],
        ]);

        // Any exceptions that are thrown while updating records are simply ignored in this method.
        // Therefore, the best we can do is to check that nothing has been updated.
        self::assertEmpty($updatedRecords);
    }

    public function testQueriedUpdate(): void
    {
        $this->expectException(ConnectionException::class);

        $this->getDbTableDataStore()->queriedUpdate(['id' => 1, 'value' => 'foo'], $this->getQuery());
    }

    public function testRewrite(): void
    {
        $this->expectException(ConnectionException::class);

        $this->getDbTableDataStore()->rewrite(['id' => 1, 'value' => 'foo']);
    }

    public function testDelete(): void
    {
        $this->expectException(ConnectionException::class);

        $this->getDbTableDataStore()->delete(1);
    }

    public function testQueriedDelete(): void
    {
        $this->expectException(ConnectionException::class);

        $this->getDbTableDataStore()->queriedDelete($this->getQuery());
    }

    public function testRead(): void
    {
        $this->expectException(ConnectionException::class);

        $this->getDbTableDataStore()->read(1);
    }

    public function testHas(): void
    {
        $this->expectException(ConnectionException::class);

        $this->getDbTableDataStore()->has(1);
    }

    public function testQuery(): void
    {
        $this->expectException(ConnectionException::class);

        $this->getDbTableDataStore()->query($this->getQuery());
    }

    private function getQuery(): Query
    {
        $query = new Query();
        $query->setQuery(new EqNode('value', 'foo'));
        return $query;
    }
}