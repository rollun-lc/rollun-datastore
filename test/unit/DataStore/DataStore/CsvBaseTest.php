<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\unit\DataStore\DataStore;

use PHPUnit\Framework\TestCase;
use rollun\datastore\DataStore\CsvBase;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\Iterators\CsvIterator;
use rollun\datastore\Rql\RqlQuery;

class CsvBaseTest extends TestCase
{
    /**
     * @var string
     */
    protected $filename;

    protected $columns = ['id', 'name', 'surname'];

    protected function setUp(): void
    {
        $this->filename = tempnam(sys_get_temp_dir(), 'csv');
        $resource = fopen($this->filename, 'w+');
        fputcsv($resource, $this->columns);
        fclose($resource);
    }

    protected function tearDown(): void
    {
        unlink($this->filename);
    }

    protected function createObject($delimiter = ',')
    {
        return new CsvBase($this->filename, $delimiter);
    }

    public function testCreateSuccess()
    {
        $item = [
            'id' => '1',
            'name' => 'name',
            'surname' => 'surname',
        ];

        $object = $this->createObject();
        $object->create($item);
        $this->assertEquals($item, $object->read($item['id']));
    }

    public function testCreateFailItemExistAndWithoutOverwrite()
    {
        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage("Item is already exist with id = 1");
        $item = [
            'id' => 1,
            'name' => 'name',
            'surname' => 'surname',
        ];
        $this->create($item);

        $object = $this->createObject();
        $object->create($item);
        $this->assertEquals($item, $this->read($item['id']));
    }

    public function testCreateFailItemExistAndWithOverwrite()
    {
        $item = [
            'id' => 1,
            'name' => 'name',
            'surname' => 'surname',
        ];
        $this->create([
            'id' => 1,
            'name' => 'name1',
            'surname' => 'surname1',
        ]);

        $object = $this->createObject();
        $object->create($item, 1);
        $this->assertEquals($item, $this->read($item['id']));
    }

    public function testCreateWithNotAllItems()
    {
        $item = [
            'id' => 1,
        ];

        $object = $this->createObject();
        $object->create($item);
        $this->assertEquals(array_merge($item, ['name' => '', 'surname' => '']), $this->read($item['id']));
    }


    public function testCreateWithNoExistingItem()
    {
        $item = [
            'id' => 1,
            'name' => 'name',
            'surname' => 'surname',
            'foo' => 'boo',
        ];

        $object = $this->createObject();
        $object->create($item);
        unset($item['foo']);
        $this->assertEquals($item, $this->read($item['id']));
    }

    public function testMultiCreateSuccess()
    {
        $object = $this->createObject();
        $items = [];

        foreach (range(1, 5) as $id) {
            $items[] = [
                $object->getIdentifier() => $id,
                'name' => "name{$id}",
                'surname' => "surname{$id}",
            ];
        }

        $object->multiCreate($items);

        foreach ($items as $item) {
            $this->assertEquals($item, $this->read($item[$object->getIdentifier()]));
        }
    }

    public function testRewriteSuccess()
    {
        $object = $this->createObject();
        $item = [
            $object->getIdentifier() => 1,
            'name' => "name1",
            'surname' => "surname1",
        ];

        $object->rewrite($item);
        $this->assertEquals($item, $this->read(1));

        $item = [
            $object->getIdentifier() => 1,
            'name' => "name2",
            'surname' => "surname2",
        ];
        $object->rewrite($item);
        $this->assertEquals($item, $this->read(1));
    }

    public function testMultiRewriteSuccess()
    {
        $object = $this->createObject();
        $items = [];
        $range = range(1, 5);

        foreach ($range as $id) {
            $items[$id] = [
                $object->getIdentifier() => $id,
                'name' => "name{$id}",
                'surname' => "surname{$id}",
            ];
        }

        $object->multiRewrite($items);

        foreach ($items as $item) {
            $this->assertEquals($item, $this->read($item[$object->getIdentifier()]));
        }

        $items = [];

        foreach ($range as $id) {
            $items[$id] = [
                $object->getIdentifier() => $id,
                'name' => "foo{$id}",
                'surname' => "bar{$id}",
            ];
        }

        $object->multiRewrite($items);

        foreach ($items as $item) {
            $this->assertEquals($item, $this->read($item[$object->getIdentifier()]));
        }
    }

    public function testUpdateSuccess()
    {
        $item = [
            'id' => 1,
            'name' => 'name',
            'surname' => 'surname',
        ];
        $this->create([
            'id' => 1,
            'name' => 'name1',
            'surname' => 'surname1',
        ]);

        $object = $this->createObject();
        $object->update($item);
        $this->assertEquals($item, $this->read($item['id']));
    }

    public function testUpdateFailItemDoesNotExist()
    {
        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage("Can't update item with id = 1: item does not exist");

        $item = [
            'id' => 1,
            'name' => 'name',
            'surname' => 'surname',
        ];

        $object = $this->createObject();
        $object->update($item);
        $this->assertEquals($item, $this->read($item['id']));
    }

    public function testUpdateFailItemDoesNotExistAndCreateIfAbsent()
    {
        $item = [
            'id' => 1,
            'surname' => 'surname2',
        ];

        $this->create([
            'id' => 1,
            'name' => 'name1',
            'surname' => 'surname1',
        ]);

        $item['name'] = 'name1';
        $object = $this->createObject();
        $object->update($item, 1);
        $this->assertEquals($item, $this->read($item['id']));
    }

    public function testUpdateNotAllItems()
    {
        $item = [
            'id' => 1,
            'name' => 'name',
            'surname' => 'surname',
        ];

        $object = $this->createObject();
        $object->update($item, 1);
        $this->assertEquals($item, $this->read($item['id']));
    }

    public function testQueriedUpdateSuccess()
    {
        $object = $this->createObject();
        $items = [];

        foreach (range(1, 5) as $id) {
            $item = [
                $object->getIdentifier() => $id,
                'name' => "name{$id}",
                'surname' => "surname{$id}",
            ];

            $object->create($item);
            $items[$id] = $item;
        }

        $query = new RqlQuery('or(eq(id,1),eq(id,3))');
        $object->queriedUpdate([
            'surname' => "foo",
        ], $query);

        foreach ([1, 3] as $id) {
            $this->assertEquals($object->read($items[$id][$object->getIdentifier()]), [
                $object->getIdentifier() => $id,
                'name' => "name{$id}",
                'surname' => "foo",
            ]);
        }
    }

    public function testQueriedDeleteSuccess()
    {
        $object = $this->createObject();
        $items = [];

        foreach (range(1, 5) as $id) {
            $item = [
                $object->getIdentifier() => $id,
                'name' => "name{$id}",
                'surname' => "surname{$id}",
            ];

            $object->create($item);
            $items[$id] = $item;
        }

        $query = new RqlQuery('or(eq(id,1),eq(id,3))');
        $object->queriedDelete($query);
        $this->assertEquals(3, count($this->getAll()));

        $object->queriedDelete(new RqlQuery());
        $this->assertEquals(0, count($this->getAll()));
    }

    public function testReadSuccess()
    {
        $items = [
            'id' => 1,
            'name' => 'name',
            'surname' => 'surname',
        ];

        $this->create($items);

        $object = $this->createObject();
        $this->assertEquals($object->read($items['id']), $this->read($items['id']));
    }
//
//    public function testMultipleRead(): void
//    {
//        $items = [
//            'id' => 1,
//            'name' => 'name',
//            'surname' => 'surname',
//        ];
//
//        $this->create($items);
//
//        $firstDataStore = $this->createObject();
//        $secondDataStore = $this->createObject();
//        $this->assertEquals($firstDataStore->read($items['id']), $this->read($items['id']));
//        $this->assertEquals($secondDataStore->read($items['id']), $this->read($items['id']));
//    }

    public function testReadSuccessWithItemNotExist()
    {
        $object = $this->createObject();
        $this->assertEquals($object->read(1), null);
    }

    public function testDeleteSuccess()
    {
        $items = [
            'id' => 1,
            'name' => 'name',
            'surname' => 'surname',
        ];

        $this->create($items);

        $object = $this->createObject();
        $this->assertEquals($object->delete($items['id']), $items);
        $this->assertEquals($this->read($items['id']), []);
    }

    public function testDeleteSuccessWithItemNotExist()
    {
        $object = $this->createObject();
        $this->assertEquals($object->delete(1), null);
    }

    public function testDeleteAll()
    {
        $range = range(1, 10);

        foreach ($range as $id) {
            $this->create([
                'id' => $id,
                'name' => "name{$id}",
                'surname' => "surname{$id}",
            ]);
        }

        $this->createObject()
            ->deleteAll();

        foreach ($range as $id) {
            $this->assertEquals($this->read($id), []);
        }
    }

    public function testCount()
    {
        $range = range(1, 10);

        foreach (range(1, 10) as $id) {
            $this->create([
                'id' => $id,
                'name' => "name{$id}",
                'surname' => "surname{$id}",
            ]);
        }

        $this->assertEquals($this->createObject()
            ->count(), count($range));
    }

    public function testGetIdentifier()
    {
        $this->assertEquals('id', $this->createObject()
            ->getIdentifier());
    }

    public function testGetIteratorSuccess()
    {
        $this->assertTrue($this->createObject()
                ->getIterator() instanceof CsvIterator);
    }

    public function testTypesSuccess()
    {
        $items = [
            'id' => 1,
            'name' => "name",
            'surname' => "surname",
        ];

        $object = $this->createObject();
        $object->create($items);
        $this->assertSame($items, $object->delete(1));

        $items['id'] = '01';
        $object->create($items);
        $this->assertSame($items, $object->delete(1));

        $items['id'] = '1';
        $object->create($items);
        $this->assertNotSame($items, $object->delete(1));
    }

    protected function read($id, $delimiter = ',')
    {
        $result = [];

        if (($handle = fopen($this->filename, 'r')) !== false) {
            $columns = fgetcsv($handle, 1000, $delimiter);
            flock($handle, LOCK_SH | LOCK_EX);

            while (($data = fgetcsv($handle, 1000, $delimiter)) !== false) {
                if (intval($data[0]) === intval($id)) {
                    for ($i = 0; $i < count($columns); $i++) {
                        $result[$columns[$i]] = $data[$i];
                    }
                }
            }

            fclose($handle);
        }

        return $result;
    }

    protected function getAll($delimiter = ',')
    {
        $result = [];

        if (($handle = fopen($this->filename, 'r')) !== false) {
            $columns = fgetcsv($handle, 1000, $delimiter);
            flock($handle, LOCK_SH | LOCK_EX);

            while (($data = fgetcsv($handle, 1000, $delimiter)) !== false) {
                for ($i = 0; $i < count($columns); $i++) {
                    $item[$columns[$i]] = $data[$i];
                }

                if (!empty($item)) {
                    $result[] = $item;
                }
            }

            fclose($handle);
        }

        return $result;
    }

    protected function create($items, $delimiter = ',')
    {
        if (($handle = fopen($this->filename, 'a')) !== false) {
            flock($handle, LOCK_SH | LOCK_EX);
            fputcsv($handle, $items, $delimiter);
            fclose($handle);
        }
    }
}
