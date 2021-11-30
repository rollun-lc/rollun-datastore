<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\unit\DataStore\DataStore;

use PHPUnit\Framework\TestCase;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\Interfaces\DataStoreInterface;
use rollun\datastore\DataStore\Traits\NoSupportCountTrait;
use rollun\datastore\DataStore\Traits\NoSupportCreateTrait;
use rollun\datastore\DataStore\Traits\NoSupportDeleteAllTrait;
use rollun\datastore\DataStore\Traits\NoSupportDeleteTrait;
use rollun\datastore\DataStore\Traits\NoSupportGetIdentifier;
use rollun\datastore\DataStore\Traits\NoSupportHasTrait;
use rollun\datastore\DataStore\Traits\NoSupportIteratorTrait;
use rollun\datastore\DataStore\Traits\NoSupportQueryTrait;
use rollun\datastore\DataStore\Traits\NoSupportReadTrait;
use rollun\datastore\DataStore\Traits\NoSupportUpdateTrait;
use rollun\datastore\Rql\RqlQuery;
use Xiag\Rql\Parser\Query;

class NoSupportTraitTest extends TestCase
{
    protected function createObject()
    {
        return new class implements DataStoreInterface {
            use NoSupportCreateTrait,
                NoSupportDeleteAllTrait,
                NoSupportGetIdentifier,
                NoSupportDeleteTrait,
                NoSupportHasTrait,
                NoSupportIteratorTrait,
                NoSupportQueryTrait,
                NoSupportReadTrait,
                NoSupportUpdateTrait,
                NoSupportCountTrait;

            public function rewrite($record)
            {
                // TODO: Implement rewrite() method.
            }

            public function multiCreate($records)
            {
                // TODO: Implement multiCreate() method.
            }

            public function multiUpdate($records)
            {
                // TODO: Implement multiUpdate() method.
            }

            public function queriedUpdate($record, Query $query)
            {
                // TODO: Implement queriedUpdate() method.
            }

            public function queriedDelete(Query $query)
            {
                // TODO: Implement queriedDelete() method.
            }
        };
    }

    public function testCreateFail()
    {
        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage("Method don't support.");
        $this->createObject()->create([]);
    }

    public function testDeleteAllFail()
    {
        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage("Method don't support.");
        $this->createObject()->deleteAll();
    }

    public function testGetIdentifierFail()
    {
        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage("Method don't support.");
        $this->createObject()->getIdentifier();
    }

    public function testDeleteFail()
    {
        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage("Method don't support.");
        $this->createObject()->delete(1);
    }

    public function testHasFail()
    {
        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage("Method don't support.");
        $this->createObject()->has(1);
    }

    public function testGetIteratorFail()
    {
        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage("Method don't support.");
        $this->createObject()->getIterator();
    }

    public function testQueryFail()
    {
        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage("Method don't support.");
        $this->createObject()->query(new RqlQuery());
    }

    public function testReadFail()
    {
        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage("Method don't support.");
        $this->createObject()->read(1);
    }

    public function testUpdateFail()
    {
        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage("Method don't support.");
        $this->createObject()->update([]);
    }

    public function testCountFail()
    {
        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage("Method don't support.");
        $this->createObject()->count();
    }
}
