<?php

namespace rollun\test\datastore\DataStore;

use Symfony\Component\Filesystem\LockHandler;
use rollun\datastore\DataStore\CsvBase;
use rollun\test\datastore\DataStore\AbstractTest;

class CsvBaseTest extends AbstractTest
{
    protected $filename;

    protected $delimiter;

    protected $entity = 'testCsvBase';

    protected function setUp()
    {
        parent::setUp();
        $this->filename = $this->config[$this->entity]['filename'];
        // If file does not exist creates it
        if (!is_file($this->filename)) {
            $fp = fopen($this->filename, 'w');
            fclose($fp);
        }
        $this->delimiter = $this->config[$this->entity]['delimiter'];
        $this->object = $this->container->get($this->entity);
    }

    protected function tearDown()
    {
        unlink($this->filename);
    }


    protected function _initObject($data = null)
    {
        if (is_null($data) || !count($data)) {
            $data = $this->_itemsArrayDelault;
        }
        if (is_null($this->filename)) {
            $this->filename = tempnam(sys_get_temp_dir(), 'csv');
        }
        $fp = fopen($this->filename, 'w');
        foreach ($data as $index => $item) {
            if (!$index) {
                // at first we write the column headings
                fputcsv($fp, array_keys($item), $this->delimiter);
            }
            fputcsv($fp, $item, $this->delimiter);
        }
        fclose($fp);
        // Set real column heading because at first created file was empty
        $this->object->getHeaders();
    }

    public function testWriteAndReadNullValueAndEmptyString()
    {
        $this->_initObject();
        $itemData = array(
            'id' => 1000,
            'anotherId' => null,
            'fFloat' => 1000.01,
            'fString' => ''
        );
        $this->object->create(
            $itemData, true
        );
        $row = $this->object->read(1000);
        $this->assertEquals(
            $itemData, $row
        );
    }


    public function testWriteAndRead_FalseValue()
    {
        $this->_initObject();
        $itemData = array(
            'id' => 1000,
            'anotherId' => false,
            'fFloat' => 1000.01,
            'fString' => 'FalseValue'
        );
        $this->object->create(
            $itemData, true
        );
        $row = $this->object->read(1000);
        $this->assertEquals(
            $row['anotherId'], false
        );
    }


    public function testWriteAndRead_TrueValue()
    {
        $this->_initObject();
        $itemData = array(
            'id' => 1000,
            'anotherId' => true,
            'fFloat' => 1000.01,
            'fString' => 'TrueValue'
        );
        $this->object->create(
            $itemData, true
        );
        $row = $this->object->read(1000);
        $this->assertEquals(
            $row['anotherId'], true
        );
    }

    public function testWriteToEmptyFile()
    {
        $itemData[] = array(
            'id' => 1000,
            'anotherId' => true,
            'fFloat' => 1000.01,
            'fString' => 'TrueValue'
        );
        $this->_initObject($itemData);
        $this->object->delete(1000);
        $itemData = array_shift($itemData);
        $this->object->create($itemData);
        $row = $this->object->read(1000);
        $this->assertEquals($row, $itemData);
    }

    public function test_getAllExpectArray()
    {
        $this->_initObject();
        clearstatcache();
        $content = $this->object->getAll();
        $this->assertTrue(
            is_array($content)
        );
    }

    public function test_getAllExpectIterator()
    {
        $this->_initObject();
        clearstatcache();
        $count = $this->object->count();
        $fp = fopen($this->filename, 'a+');
        $itemData = $this->_itemsArrayDelault[$count - 1];
        while (filesize($this->filename) <= CsvBase::MAX_FILE_SIZE_FOR_CACHE + 100) {
            $count++;
            $itemData['id'] = $count;
            fputcsv($fp, $itemData, $this->delimiter);
            clearstatcache();
        }
        fclose($fp);
        $content = $this->object->getAll();
        $this->assertTrue(
            $content instanceof \Traversable
        );
    }
}