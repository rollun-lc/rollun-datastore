<?php

namespace rollun\test\functional\DataStore\DataStore\OperationTimedOutExceptionTest;

use rollun\datastore\DataStore\Interfaces\DataStoreInterface;
use rollun\datastore\DataStore\OperationTimedOutException;
use rollun\test\functional\FunctionalTestCase;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\EqNode;
use Xiag\Rql\Parser\Query;

abstract class BaseTest extends FunctionalTestCase
{
    /**
     * Should return dataStore that have problems with connection
     */
    abstract protected function getDataStore(): DataStoreInterface;

    public function testCreate(): void
    {
        $this->expectException(OperationTimedOutException::class);

        $this->getDataStore()->create(['id' => 1, 'value' => 'foo']);
    }

    public function testMultiCreate(): void
    {
        $this->expectException(OperationTimedOutException::class);

        $this->getDataStore()->multiCreate([
            ['id' => 1, 'value' => 'foo'],
            ['id' => 2, 'value' => 'bar'],
        ]);
    }

    public function testUpdate(): void
    {
        $this->expectException(OperationTimedOutException::class);

        $this->getDataStore()->update(['id' => 1, 'value' => 'foo']);
    }

    public function testMultiUpdate(): void
    {
        $this->expectException(OperationTimedOutException::class);

        $this->getDataStore()->multiUpdate([
            ['id' => 1, 'value' => 'foo'],
            ['id' => 2, 'value' => 'bar'],
        ]);
    }

    public function testQueriedUpdate(): void
    {
        $this->expectException(OperationTimedOutException::class);

        $this->getDataStore()->queriedUpdate(['id' => 1, 'value' => 'foo'], $this->getQuery());
    }

    public function testRewrite(): void
    {
        $this->expectException(OperationTimedOutException::class);

        $this->getDataStore()->rewrite(['id' => 1, 'value' => 'foo']);
    }

    public function testDelete(): void
    {
        $this->expectException(OperationTimedOutException::class);

        $this->getDataStore()->delete(1);
    }

    public function testQueriedDelete(): void
    {
        $this->expectException(OperationTimedOutException::class);

        $this->getDataStore()->queriedDelete($this->getQuery());
    }

    public function testRead(): void
    {
        $this->expectException(OperationTimedOutException::class);

        $this->getDataStore()->read(1);
    }

    public function testHas(): void
    {
        $this->expectException(OperationTimedOutException::class);

        $this->getDataStore()->has(1);
    }

    public function testQuery(): void
    {
        $this->expectException(OperationTimedOutException::class);

        $this->getDataStore()->query($this->getQuery());
    }

    private function getQuery(): Query
    {
        $query = new Query();
        $query->setQuery(new EqNode('value', 'foo'));
        return $query;
    }
}
