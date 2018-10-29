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
        $this->assertEquals($item, 'item', $object);
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
}
