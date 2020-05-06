<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\unit\DataStore\DataStore;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\Memory;
use rollun\datastore\Rql\RqlParser;
use rollun\datastore\Rql\RqlQuery;

class MemoryTest extends TestCase
{
    protected function createObject($columns = [])
    {
        return new Memory($columns);
    }

    /**
     * @return array
     */
    public function getQueryDataProvider(): array
    {
        $data = [
            [
                'id'    => 1,
                'name'  => 'name 1',
                'price' => 440,
            ],
            [
                'id'    => 2,
                'name'  => 'name 2',
                'price' => 500,
            ],
            [
                'id'    => 3,
                'name'  => 'name 3',
                'price' => 1500,
            ],
            [
                'id'    => 4,
                'name'  => 'name 4',
                'price' => 2250,
            ],
            [
                'id'    => 5,
                'name'  => 'name 2',
                'price' => 10,
            ]
        ];

        return [
            [$data, 'select(max(price))', '[{"max(price)":2250}]'],
            [$data, 'select(min(id))', '[{"min(id)":1}]'],
            [$data, 'select(max(price))&le(id,3)', '[{"max(price)":1500}]'],
            [$data, 'select(max(price))&gt(id,15)', '[{"max(price)":null}]'],
            [$data, 'select(id)&gt(id,2)&limit(1,1)', '[{"id":4}]'],
            [$data, 'select(id,sum(price))&not(eq(id,5))&sort(-id)&limit(1,0)', '[{"id":4,"sum(price)":4690}]'],
            [$data, 'select(id,name,price)&sort(-name)&limit(2,1)', '[{"id":3,"name":"name 3","price":1500},{"id":2,"name":"name 2","price":500}]'],
        ];
    }

    /**
     * @param array  $data
     * @param string $query
     * @param string $expected
     *
     * @dataProvider getQueryDataProvider
     */
    public function testQuery(array $data, string $query, string $expected)
    {
        $memory = $this->createObject();
        foreach ($data as $row) {
            $memory->create($row);
        }

        $this->assertEquals($expected, json_encode($memory->query(RqlParser::rqlDecode($query))));
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

    public function testMultiUpdateSuccess()
    {
        $object = $this->createObject(['id', 'name', 'surname']);
        $items = [];

        foreach (range(1, 5) as $id) {
            $items[$id] = [
                'id' => $id,
                'name' => "name{$id}",
                'surname' => "surname{$id}",
            ];
        }

        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty('items');
        $property->setAccessible(true);
        $property->setValue($object, $items);

        foreach ($items as $item) {
            $id = $item['id'];
            $items[$id] = [
                'id' => $id,
                'name' => "foo{$id}",
                'surname' => "bar{$id}",
            ];
        }

        $object->multiUpdate($items);
        $this->assertAttributeEquals($items, 'items', $object);
    }

    public function testQueriedUpdate()
    {
        $object = $this->createObject(['id', 'name', 'surname']);
        $items = [];

        foreach (range(1, 5) as $id) {
            $items[$id] = [
                'id' => $id,
                'name' => "name{$id}",
                'surname' => "surname{$id}",
            ];
        }

        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty('items');
        $property->setAccessible(true);
        $property->setValue($object, $items);

        $query = new RqlQuery('or(eq(id,1),eq(id,5))');
        $object->queriedUpdate([
            'name' => "foo",
            'surname' => "bar",
        ], $query);

        $actualItems = $property->getValue($object);

        foreach ([1, 5] as $id) {
            $this->assertEquals([
                'id' => $id,
                'name' => "foo",
                'surname' => "bar",
            ], $actualItems[$id]);
        }
    }

    public function testQueriedDelete()
    {
        $object = $this->createObject(['id', 'name', 'surname']);
        $items = [];

        foreach (range(1, 5) as $id) {
            $items[$id] = [
                'id' => $id,
                'name' => "name{$id}",
                'surname' => "surname{$id}",
            ];
        }

        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty('items');
        $property->setAccessible(true);
        $property->setValue($object, $items);

        $query = new RqlQuery('or(eq(id,1),eq(id,5))');
        $object->queriedDelete($query);

        $this->assertEquals(3, count($property->getValue($object)));

        $object->queriedDelete(new RqlQuery());
        $this->assertEquals(0, count($property->getValue($object)));
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

    public function testMultiCreateSuccess()
    {
        $object = $this->createObject(['id', 'name']);
        $items = [];

        foreach (range(1, 5) as $id) {
            $items[$id] = [
                'id' => $id,
                'name' => "name{$id}",
            ];
        }

        $object->multiCreate($items);
        $this->assertAttributeEquals($items, 'items', $object);
    }

    public function testMultiRewriteSuccess()
    {
        $object = $this->createObject(['id', 'name']);
        $items = [];
        $range = range(1, 5);

        foreach ($range as $id) {
            $items[$id] = [
                $object->getIdentifier() => $id,
                'name' => "name{$id}",
            ];
        }

        $object->multiRewrite($items);
        $this->assertAttributeEquals($items, 'items', $object);

        $items = [];

        foreach ($range as $id) {
            $items[$id] = [
                $object->getIdentifier() => $id,
                'name' => "foo{$id}",
            ];
        }

        $object->multiRewrite($items);
        $this->assertAttributeEquals($items, 'items', $object);
    }

    public function testRewriteSuccess()
    {
        $object = $this->createObject();
        $item = [
            $object->getIdentifier() => 1,
            'name' => "name1",
        ];

        $object->rewrite($item);
        $this->assertEquals($item, $object->read(1));

        $item = [
            $object->getIdentifier() => 1,
            'name' => "name2",
        ];
        $object->rewrite($item);
        $this->assertEquals($item, $object->read(1));
    }

    public function testReadNotExistingItem()
    {
        $this->assertEquals(null, $this->createObject()->read(1));
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
        $property->setValue(
            $object,
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
        $property->setValue(
            $object,
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
        $property->setValue(
            $object,
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
        $this->assertEquals(2, $object->count());
    }
}
