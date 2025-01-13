<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\unit\DataStore\DataStore;

use rollun\datastore\DataStore\CsvIntId;
use rollun\datastore\DataStore\DataStoreException;

class CsvIntIdTest extends CsvBaseTestCase
{
    protected function createDataStore(): CsvIntId
    {
        return new CsvIntId($this->filename, $this->getDelimiter());
    }

    public function testCreateSuccess()
    {
        $item = [
            'id' => 1,
            'name' => 'name',
            'surname' => 'surname',
        ];

        $object = $this->createDataStore();
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

        $object = $this->createDataStore();
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

        $object = $this->createDataStore();
        $object->create($items);
        $this->assertSame($items, $object->delete(1));
    }

    protected function getDelimiter(): string
    {
        return ',';
    }
}
