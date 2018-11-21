<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\unit\DataStore\DataStore;

use PHPUnit\Framework\TestCase;
use rollun\datastore\DataStore\Cacheable;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\Interfaces\DataSourceInterface;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use rollun\datastore\DataStore\Memory;
use rollun\datastore\Rql\RqlQuery;

class CacheableTest extends TestCase
{
    public function createObject(DataSourceInterface $dataSource, DataStoresInterface $cashStore = null)
    {
        return new Cacheable($dataSource, $cashStore);
    }

    public function testQuery()
    {
        $rqlQuery = new RqlQuery('eq(id,1)');

        /** @var DataSourceInterface|\PHPUnit_Framework_MockObject_MockObject $dataSource */
        $dataSource = $this->getMockBuilder(DataSourceInterface::class)
            ->getMock();

        /** @var DataStoresInterface|\PHPUnit_Framework_MockObject_MockObject $dataSource */
        $cashStore = $this->getMockBuilder(DataStoresInterface::class)
            ->getMock();
        $cashStore->expects($this->once())
            ->method('query')
            ->with($rqlQuery);

        $this->createObject($dataSource, $cashStore)
            ->query($rqlQuery);
    }

    public function testRead()
    {
        $id = 1;

        /** @var DataSourceInterface|\PHPUnit_Framework_MockObject_MockObject $dataSource */
        $dataSource = $this->getMockBuilder(DataSourceInterface::class)
            ->getMock();

        /** @var DataStoresInterface|\PHPUnit_Framework_MockObject_MockObject $dataSource */
        $cashStore = $this->getMockBuilder(DataStoresInterface::class)
            ->getMock();
        $cashStore->expects($this->once())
            ->method('read')
            ->with($id);

        $this->createObject($dataSource, $cashStore)
            ->read($id);
    }

    public function testGetIterator()
    {
        /** @var DataSourceInterface|\PHPUnit_Framework_MockObject_MockObject $dataSource */
        $dataSource = $this->getMockBuilder(DataSourceInterface::class)
            ->getMock();

        /** @var DataStoresInterface|\PHPUnit_Framework_MockObject_MockObject $dataSource */
        $cashStore = $this->getMockBuilder(DataStoresInterface::class)
            ->getMock();
        $cashStore->expects($this->once())
            ->method('getIterator');

        $this->createObject($dataSource, $cashStore)
            ->getIterator();
    }

    public function testGetIdentifier()
    {
        /** @var DataSourceInterface|\PHPUnit_Framework_MockObject_MockObject $dataSource */
        $dataSource = $this->getMockBuilder(DataSourceInterface::class)
            ->getMock();

        /** @var DataStoresInterface|\PHPUnit_Framework_MockObject_MockObject $dataSource */
        $cashStore = $this->getMockBuilder(DataStoresInterface::class)
            ->getMock();
        $cashStore->expects($this->once())
            ->method('getIdentifier');

        $this->createObject($dataSource, $cashStore)
            ->getIdentifier();
    }

    public function testHas()
    {
        $id = 1;

        /** @var DataSourceInterface|\PHPUnit_Framework_MockObject_MockObject $dataSource */
        $dataSource = $this->getMockBuilder(DataSourceInterface::class)
            ->getMock();

        /** @var DataStoresInterface|\PHPUnit_Framework_MockObject_MockObject $dataSource */
        $cashStore = $this->getMockBuilder(DataStoresInterface::class)
            ->getMock();
        $cashStore->expects($this->once())
            ->method('has')
            ->with($id);

        $this->createObject($dataSource, $cashStore)
            ->has($id);
    }

    public function testCount()
    {
        /** @var DataSourceInterface|\PHPUnit_Framework_MockObject_MockObject $dataSource */
        $dataSource = $this->getMockBuilder(DataSourceInterface::class)
            ->getMock();

        /** @var DataStoresInterface|\PHPUnit_Framework_MockObject_MockObject $dataSource */
        $cashStore = $this->getMockBuilder(DataStoresInterface::class)
            ->getMock();
        $cashStore->expects($this->once())
            ->method('count');

        $this->createObject($dataSource, $cashStore)
            ->count();
    }

    public function testCreateSuccess()
    {
        $items = [];

        $dataSource = new Foo();

        /** @var DataStoresInterface|\PHPUnit_Framework_MockObject_MockObject $dataSource */
        $cashStore = $this->getMockBuilder(DataStoresInterface::class)
            ->getMock();

        $this->createObject($dataSource, $cashStore)
            ->create($items);
    }

    public function testCreateFail()
    {
        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage("Refreshable don't haw method create");
        $items = [];

        /** @var DataSourceInterface|\PHPUnit_Framework_MockObject_MockObject $dataSource */
        $dataSource = new Boo();

        /** @var DataStoresInterface|\PHPUnit_Framework_MockObject_MockObject $dataSource */
        $cashStore = $this->getMockBuilder(DataStoresInterface::class)
            ->getMock();

        $this->createObject($dataSource, $cashStore)
            ->create($items);
    }

    public function testUpdateSuccess()
    {
        $items = ['id' => 1];

        /** @var DataSourceInterface|\PHPUnit_Framework_MockObject_MockObject $dataSource */
        $dataSource = $this->getMockBuilder(Foo::class)
            ->getMock();
        $dataSource->expects($this->once())
            ->method('update')
            ->with($items, 1);

        /** @var DataStoresInterface|\PHPUnit_Framework_MockObject_MockObject $dataSource */
        $cashStore = $this->getMockBuilder(DataStoresInterface::class)
            ->getMock();

        $this->createObject($dataSource, $cashStore)
            ->update($items, 1);
    }

    public function testUpdateFail()
    {
        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage("Refreshable don't haw method update");
        $items = [];

        /** @var DataSourceInterface|\PHPUnit_Framework_MockObject_MockObject $dataSource */
        $dataSource = new Boo();

        /** @var DataStoresInterface|\PHPUnit_Framework_MockObject_MockObject $dataSource */
        $cashStore = $this->getMockBuilder(DataStoresInterface::class)
            ->getMock();

        $this->createObject($dataSource, $cashStore)
            ->update($items);
    }

    public function testDeleteAllSuccess()
    {

        /** @var DataSourceInterface|DataStoreException|\PHPUnit_Framework_MockObject_MockObject $dataSource */
        $dataSource = $this->getMockBuilder(Foo::class)
            ->getMock();
        $dataSource->expects($this->once())
            ->method('deleteAll');

        /** @var DataStoresInterface|\PHPUnit_Framework_MockObject_MockObject $dataSource */
        $cashStore = $this->getMockBuilder(DataStoresInterface::class)
            ->getMock();

        $this->createObject($dataSource, $cashStore)
            ->deleteAll();
    }

    public function testDeleteAllFail()
    {
        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage("Refreshable don't haw method deleteAll");

        /** @var DataSourceInterface|DataStoreException|\PHPUnit_Framework_MockObject_MockObject $dataSource */
        $dataSource = new Boo();

        /** @var DataStoresInterface|\PHPUnit_Framework_MockObject_MockObject $dataSource */
        $cashStore = $this->getMockBuilder(DataStoresInterface::class)
            ->getMock();

        $this->createObject($dataSource, $cashStore)
            ->deleteAll();
    }

    public function testDeleteSuccess()
    {
        $id = 1;

        /** @var DataSourceInterface|DataStoreException|\PHPUnit_Framework_MockObject_MockObject $dataSource */
        $dataSource = $this->getMockBuilder(Foo::class)
            ->getMock();
        $dataSource->expects($this->once())
            ->method('delete')
            ->with($id);

        /** @var DataStoresInterface|\PHPUnit_Framework_MockObject_MockObject $dataSource */
        $cashStore = $this->getMockBuilder(DataStoresInterface::class)
            ->getMock();

        $this->createObject($dataSource, $cashStore)
            ->delete($id);
    }

    public function testDeleteFail()
    {
        $id = 1;
        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage("Refreshable don't haw method delete");

        /** @var DataSourceInterface|DataStoreException|\PHPUnit_Framework_MockObject_MockObject $dataSource */
        $dataSource = new Boo();

        /** @var DataStoresInterface|\PHPUnit_Framework_MockObject_MockObject $dataSource */
        $cashStore = $this->getMockBuilder(DataStoresInterface::class)
            ->getMock();

        $this->createObject($dataSource, $cashStore)
            ->delete($id);
    }

    public function testRefresh()
    {

        $dataSource = new Foo();
        $items = [];

        foreach (range(1, 10) as $id) {
            $items[] = $dataSource->create([
                'id' => $id,
                'name' => "name{$id}",
            ]);
        }

        $cacheStore = new Foo();
        $object = $this->createObject($dataSource, $cacheStore);

        foreach ($items as $item) {
            $this->assertEquals($object->read($item['id']), null);
        }

        $object->refresh();

        foreach ($items as $item) {
            $this->assertEquals($object->read($item['id']), $item);
        }
    }
}

class Boo implements DataSourceInterface
{
    public function getAll()
    {
        return [];
    }
}

class Foo extends Memory implements DataSourceInterface
{
    public function getAll()
    {
        $keys = $this->getKeys();
        $items = [];

        foreach ($keys as $key) {
            $items[] = $this->read($key);
        }

        return $items;
    }
}
