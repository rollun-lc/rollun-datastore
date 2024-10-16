<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\unit\DataStore\DataStore;

use rollun\datastore\DataStore\CsvIntId;
use rollun\datastore\DataStore\DataStoreException;

class CsvIntIdTest extends CsvBaseTest
{
    protected function createObject($delimiter = ',')
    {
        return new CsvIntId($this->filename, $delimiter);
    }

    public function testCreateSuccess()
    {
        $item = [
            'id' => 1,
            'name' => 'name',
            'surname' => 'surname',
        ];

        $object = $this->createObject();
        $object->create($item);
        $this->assertSame($item, $object->read($item['id']));
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
    }

    public function testTypesFail()
    {
        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage("This storage type supports integer primary keys only");
        $items = [
            'id' => '1',
            'name' => "name",
            'surname' => "surname",
        ];

        $object = $this->createObject();
        $object->create($items);
        $this->assertSame($items, $object->delete(1));
    }
}
