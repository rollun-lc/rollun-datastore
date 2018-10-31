<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace test\unit\DataStore\DataStore;

use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_Error_Deprecated;
use ReflectionClass;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\Memory;

class MemoryTest extends TestCase
{
    protected function createObject($columns = [], $muteDeprecatedError = true)
    {
        if (!count($columns) && $muteDeprecatedError) {
            PHPUnit_Framework_Error_Deprecated::$enabled = false;
        }

        return new Memory($columns);
    }

    public function testCreateSuccess()
    {
        $this->expectException(PHPUnit_Framework_Error_Deprecated::class);
        $this->expectExceptionMessage('Array of required columns is not specified');
        $item = [
            'id' => 1,
            'name' => 'name'
        ];
        $object = $this->createObject([], false);
        $object->create($item);
        $this->assertAttributeEquals([1 =>$item], 'items', $object);
    }

    public function testCreateFailWithItemExist()
    {
        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage("Item with id '1' already exist");
        $object = $this->createObject();
        $object->create([
            'id' => 1,
            'name' => 'name1'
        ]);
        $object->create([
            'id' => 1,
            'name' => 'name2'
        ]);
    }

    public function testUpdateSuccess()
    {
        $item[1] = [
            'id' => 1,
            'name' => 'name1',
            'surname' => 'surname1'
        ];
        $object = $this->createObject();
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty('items');
        $property->setAccessible(true);
        $property->setValue($object, $item);
        $object->update([
            'id' => 1,
            'name' => 'name2'
        ]);
        $this->assertAttributeEquals([1 => [
            'id' => 1,
            'name' => 'name2',
            'surname' => 'surname1',
        ]], 'items', $object);
    }

    public function testUpdateFailWithItemHasNotPrimaryKey()
    {
        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage('Item must has primary key');
        $object = $this->createObject();
        $object->update([
            'name' => 'name'
        ]);
    }

    public function testUpdateFailWithItemDoesNotExist()
    {
        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage("Item doesn't exist with id = 1");
        $object = $this->createObject();
        $object->update([
            'id' => 1,
            'name' => 'name'
        ]);
    }

    public function testUpdateCreateSuccessWithExistingField()
    {
        $object = $this->createObject(['id', 'name', 'surname']);
        $itemData1 = [
            'id' => 1,
            'name' => 'name1',
            'surname' => 'surname1',
        ];
        $object->create($itemData1);
        $this->assertAttributeEquals([1 => $itemData1], 'items', $object);

        $itemData2 = [
            'id' => 1,
            'name' => 'name2',
            'surname' => 'surname2',
        ];
        $object->update($itemData2);
        $this->assertAttributeEquals([1 => $itemData2], 'items', $object);
    }

    public function testCreateUpdateFailWithNotExistingField()
    {
        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage("Undefined field 'notExistingField' in data store");
        $object = $this->createObject(['id', 'name']);
        $object->create([
            'id' => 1,
            'name' => 'name',
            'notExistingField' => 'anyValue',
        ]);

        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage("Undefined field 'notExistingField' in data store");
        $object->update([
            'id' => 1,
            'name' => 'name',
            'notExistingField' => 'anyValue',
        ]);
    }

    public function testRead()
    {
        $item[1] = [
            'id' => 1,
            'name' => 'name1'
        ];
        $object = $this->createObject();
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty('items');
        $property->setAccessible(true);
        $property->setValue($object, $item);
        $this->assertEquals($item[1], $object->read(1));
        $this->assertEquals(null, $object->read(2));
    }

    public function testDelete()
    {
        $object = $this->createObject();
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty('items');
        $property->setAccessible(true);
        $property->setValue($object,
            [
                1 => [
                    'id' => 1,
                    'name' => 'name1'
                ]
            ]
        );
        $object->delete(1);
        $this->assertAttributeEquals([], 'items', $object);
    }

    public function testDeleteAll()
    {
        $object = $this->createObject();
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty('items');
        $property->setAccessible(true);
        $property->setValue($object,
            [
                1 => [
                    'id' => 1,
                    'name' => 'name1'
                ],
                2 => [
                    'id' => 2,
                    'name' => 'name1'
                ]
            ]
        );
        $object->deleteAll();
        $this->assertAttributeEquals([], 'items', $object);
    }

    public function testCount()
    {
        $object = $this->createObject();
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty('items');
        $property->setAccessible(true);
        $property->setValue($object,
            [
                1 => [
                    'id' => 1,
                    'name' => 'name1'
                ],
                2 => [
                    'id' => 2,
                    'name' => 'name1'
                ]
            ]
        );
        $this->assertEquals(2,  $object->count());
    }

    public function testGetIteratorIsDeprecated()
    {
        $this->expectException(PHPUnit_Framework_Error_Deprecated::class);
        $this->expectExceptionMessage('Datastore is not iterable no more');
        $object = $this->createObject();
        PHPUnit_Framework_Error_Deprecated::$enabled = true;
        $object->getIterator();
    }
}
