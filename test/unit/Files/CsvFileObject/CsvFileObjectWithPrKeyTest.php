<?php

namespace rollun\test\unit\Files\CsvFileObject;

use rollun\files\Csv\CsvFileObjectWithPrKey as FileObjectWithPrKey;
use rollun\files\Csv\Strategy\CsvBinaryStrategy;
use rollun\files\FileManager;
use rollun\test\unit\Files\FilesAbstractTest;

class CsvFileObjectWithPrKeyTest extends FilesAbstractTest
{
    /**
     * @inheritDoc
     */
    protected function tearDown()
    {
        // delete tmp files and dirs
        (new FileManager())->deleteDirRecursively('data/test/unit/files/csvfileobject/csvfileobjectwithprkeytest/');
    }

    public function getRowByIdProvider()
    {
        // $id, $content, $expected
        $data = [
            // no such row
            ['2q', "id,val\r\n\"1q\",\"qqq\"", null],
            ['2q', "id,val", null],
            ['2q', "id,val\r\n\"1q\",\"qqq\"\r\n\"3q\",\"qqq\"", null],
            ['1', "id,val\r\n\"1q\",\"qqq\"\r\n\"3q\",\"qqq\"", null],
            // row at the beginning
            ['1q', "id,val\r\n\"1q\",\"qqq\"", ["1q", "qqq"]],
            ['1q', "id,val\r\n\"1q\",\"qqq\"\r\n\"2q\",\"qqq\"\r\n\"3q\",\"qqq\"", ["1q", "qqq"]],
            ['1q', "id,val\r\n\"1q\",\"qqq\"\r\n\"2q\",\"qqq\"\r\n\"3q\",\"qqq\"\r\n\"4q\",\"qqq\"", ["1q", "qqq"]],
            // row in the middle
            ['2q', "id,val\r\n\"1q\",\"qqq\"\r\n\"2q\",\"qqq\"\r\n\"3q\",\"qqq\"", ["2q", "qqq"]],
            ['3q', "id,val\r\n\"1q\",\"qqq\"\r\n\"2q\",\"qqq\"\r\n\"3q\",\"qqq\"\r\n\"4q\",\"qqq\"", ["3q", "qqq"]],
            ['2q', "id,val\r\n\"1q\",\"qqq\"\r\n\"2q\",\"qqq\"\r\n\"3q\",\"qqq\"\r\n\"4q\",\"qqq\"", ["2q", "qqq"]],
            // row in the end
            ['2q', "id,val\r\n\"1q\",\"qqq\"\r\n\"2q\",\"qqq\"", ["2q", "qqq"]],
            ['4q', "id,val\r\n\"1q\",\"qqq\"\r\n\"2q\",\"qqq\"\r\n\"3q\",\"qqq\"\r\n\"4q\",\"qqq\"", ["4q", "qqq"]],
            ['6q', "id,val\r\n\"1q\",\"qqq\"\r\n\"2q\",\"qqq\"\r\n\"3q\",\"qqq\"\r\n\"4q\",\"qqq\"\r\n\"5q\",\"qqq\"\r\n\"6q\",\"qqq\"", ["6q", "qqq"]],
        ];

        return $data;
    }

    /**
     * @dataProvider getRowByIdProvider
     */
    public function testGetRowByIdBinary($id, $content, $expected)
    {
        $csvWithPrKeyFileObject = new FileObjectWithPrKey($this->makeFile($content));

        if ($expected === null) {
            $this->assertNull($csvWithPrKeyFileObject->getRowById($id));
        } else {
            $this->assertEquals($expected, $csvWithPrKeyFileObject->getRowById($id));
        }
    }

    public function addRowProvider()
    {
        // $content $row, $expected
        return [
            // add row to the beginning
            ["id,val", ['1q', 'qqq'], "id,val\r\n\"1q\",\"qqq\""],
            ["id,val", ['01', 'qqq'], "id,val\r\n\"01\",\"qqq\""],
            ["id,val\r\n\"1q\",\"qqq\"", [1, 'qqq'], "id,val\r\n\"1\",\"qqq\"\r\n\"1q\",\"qqq\""],
            ["id,val", [5, 55], "id,val\r\n\"5\",\"55\""],
            // add row to the middle
            ["id,val\r\n\"1q\",\"qqq\"\r\n\"3q\",\"qqq\"", ['2q', 'qqq'], "id,val\r\n\"1q\",\"qqq\"\r\n\"2q\",\"qqq\"\r\n\"3q\",\"qqq\""],
            ["id,val\r\n\"1\",\"qqq\"\r\n\"3q\",\"qqq\"", ['2q', 'qqq'], "id,val\r\n\"1\",\"qqq\"\r\n\"2q\",\"qqq\"\r\n\"3q\",\"qqq\""],
            ["id,val\r\n\"1\",\"10\"\r\n\"3\",\"30\"", [2, 20], "id,val\r\n\"1\",\"10\"\r\n\"2\",\"20\"\r\n\"3\",\"30\""],
            ["id,val\r\n\"1q\",\"qqq\"\r\n\"3q\",\"qqq\"\r\n\"5q\",\"qqq\"", ['4q', 'qqq'], "id,val\r\n\"1q\",\"qqq\"\r\n\"3q\",\"qqq\"\r\n\"4q\",\"qqq\"\r\n\"5q\",\"qqq\""],
            // add row to the end
            ["id,val\r\n\"1q\",\"qqq\"\r\n\"3q\",\"qqq\"", ['4q', 'qqq'], "id,val\r\n\"1q\",\"qqq\"\r\n\"3q\",\"qqq\"\r\n\"4q\",\"qqq\""],
            ["id,val\r\n\"1q\",\"qqq\"\r\n\"3q\",\"qqq\"", ['99q', 'qqq'], "id,val\r\n\"1q\",\"qqq\"\r\n\"3q\",\"qqq\"\r\n\"99q\",\"qqq\""],
            ["id,val\r\n\"1q\",\"qqq\"", ['q ', 'qqq'], "id,val\r\n\"1q\",\"qqq\"\r\n\"q \",\"qqq\""],
            ["id,val\r\n", [12, 55], "id,val\r\n\"12\",\"55\"\r\n"],
            ["id,val\r\n\"1q\",\"qqq\"\r\n", ['2q', 56], "id,val\r\n\"1q\",\"qqq\"\r\n\"2q\",\"56\"\r\n"],
        ];
    }

    /**
     * @dataProvider addRowProvider
     */
    public function testAddRowBinary($content, $row, $expected)
    {
        $fileObjectWithPrKey = new FileObjectWithPrKey($this->makeFile($content));

        $fileObjectWithPrKey->addRow($row);

        $fileObjectWithPrKey->getFileObject()->fseekWithCheck(0);
        $actualString = $fileObjectWithPrKey->getFileObject()->fread(100);

        $this->assertEquals($expected, $actualString);
    }

    public function addRowThrowExceptionProvider()
    {
        // $content $row
        return [
            ["id,val\r\n\"1\",\"qqq\"", [1, '1qqq']],
            ["id,val\r\n\"1\",\"qqq\"\r\n\"2\",\"qqq\"", [1, '1qqq']],
            ["id,val\r\n\"1q\",\"qqq\"\r\n\"3q\",\"qqq\"", ['3q', 'qqq']],
            ["id,val\r\n\"1q\",\"qqq\"\r\n\"3q\",\"qqq\"", ['1q', '']],
            ["id,val\r\n\"1\",\"qqq\"\r\n\"3q\",\"qqq\"", [1, 'qqqs']],
        ];
    }

    /**
     * @dataProvider addRowThrowExceptionProvider
     */
    public function testAddRowThrowExceptionBinary($content, $row)
    {
        $fileObjectWithPrKey = new FileObjectWithPrKey($this->makeFile($content));
        try {
            $fileObjectWithPrKey->addRow($row);
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('Row with such ID already exists', $e->getMessage());
        }
    }

    public function setRowProvider()
    {
        // $content $row, $expected
        return [
            // edit row in the beginning
            ["id,val\r\n\"1q\",\"qqq\"", ['1q', 'qqq1'], "id,val\r\n\"1q\",\"qqq1\""],
            ["id,val\r\n\"1\",\"10\"", [1, 11], "id,val\r\n\"1\",\"11\""],
            ["id,val\r\n\"1q\",\"qqq\"\r\n\"3q\",\"qqq\"", ['1q', 'qqq1'], "id,val\r\n\"1q\",\"qqq1\"\r\n\"3q\",\"qqq\""],
            // edit row in the middle
            ["id,val\r\n\"1\",\"qqq\"\r\n\"2\",\"qqq\"\r\n\"3q\",\"qqq\"", [2, 15], "id,val\r\n\"1\",\"qqq\"\r\n\"2\",\"15\"\r\n\"3q\",\"qqq\""],
            ["id,val\r\n\"1\",\"qqq\"\r\n\"2\",\"qqq\"\r\n\"3q\",\"qqq\"", [2, 155], "id,val\r\n\"1\",\"qqq\"\r\n\"2\",\"155\"\r\n\"3q\",\"qqq\""],
            ["id,val\r\n\"1\",\"qqq\"\r\n\"2\",\"qqq\"\r\n\"3q\",\"qqq\"", [2, 1555], "id,val\r\n\"1\",\"qqq\"\r\n\"2\",\"1555\"\r\n\"3q\",\"qqq\""],
            ["id,val\r\n\"1q\",\"qqq\"\r\n\"2q\",\"qqq\"\r\n\"3q\",\"qqq\"", ['2q', ''], "id,val\r\n\"1q\",\"qqq\"\r\n\"2q\",\"\"\r\n\"3q\",\"qqq\""],
            ["id,val\r\n\"1q\",\"qqq\"\r\n\"2q\",\"qqq\"\r\n\"3q\",\"qqq\"", ['2q', "test\ntest"], "id,val\r\n\"1q\",\"qqq\"\r\n\"2q\",\"test\ntest\"\r\n\"3q\",\"qqq\""],
            // edit row in the end
            ["id,val\r\n\"1\",\"qqq\"\r\n\"2\",\"qqq\"\r\n\"3\",\"qqq\"", [3, 15], "id,val\r\n\"1\",\"qqq\"\r\n\"2\",\"qqq\"\r\n\"3\",\"15\""],
            ["id,val\r\n\"1\",\"qqq\"\r\n\"2\",\"qqq\"\r\n\"3\",\"qqq\"", [3, 1515], "id,val\r\n\"1\",\"qqq\"\r\n\"2\",\"qqq\"\r\n\"3\",\"1515\""],
            ["id,val\r\n\"1q\",\"qqq\"\r\n\"2q\",\"qqq\"\r\n\"3q\",\"qqq\"", ['3q', ''], "id,val\r\n\"1q\",\"qqq\"\r\n\"2q\",\"qqq\"\r\n\"3q\",\"\""],
        ];
    }

    /**
     * @dataProvider setRowProvider
     */
    public function testSetRowBinary($content, $row, $expected)
    {
        $fileObjectWithPrKey = new FileObjectWithPrKey($this->makeFile($content));
        $fileObjectWithPrKey->setRow($row);
        $fileObjectWithPrKey->getFileObject()->fseekWithCheck(0);
        $actualString = $fileObjectWithPrKey->getFileObject()->fread(100);

        $this->assertEquals($expected, $actualString);
    }

    public function editRowThrowExceptionProvider()
    {
        // $content $row
        return [
            ["id,val\r\n\"1\",\"qqq\"", [2, '1qqq']],
            ["id,val\r\n\"1\",\"qqq\"\r\n\"2\",\"qqq\"", ['qwe', '1qqq']],
            ["id,val\r\n\"1q\",\"qqq\"\r\n\"3q\",\"qqq\"", ['2q', 'qqq']],
            ["id,val\r\n\"1q\",\"qqq\"\r\n\"3q\",\"qqq\"", [2, '']],
            ["id,val\r\n\"1\",\"qqq\"\r\n\"3q\",\"qqq\"", [15, 'qqqs']],
        ];
    }

    /**
     * @dataProvider editRowThrowExceptionProvider
     */
    public function testEditRowThrowExceptionBinary($content, $row)
    {
        $fileObjectWithPrKey = new FileObjectWithPrKey($this->makeFile($content));
        try {
            $fileObjectWithPrKey->setRow($row);
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('No row with such ID', $e->getMessage());
        }
    }

    public function testCreateObjectWithWrongStrategy()
    {
        try {
            new FileObjectWithPrKey($this->makeFile('id,val'), ',', '"', '\\', '\\App\\Bla\\Bla\\Bla');
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals("CsvStrategy does not exist", $e->getMessage());
        }

        try {
            new FileObjectWithPrKey($this->makeFile('id,val'), ',', '"', '\\', \stdClass::class);
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals("CsvStrategy should be instanceof CsvStrategyInterface", $e->getMessage());
        }
    }

    public function testCreateObjectWithWrongIdentifier()
    {
        try {
            new FileObjectWithPrKey($this->makeFile('id,val'), ',', '"', '\\', CsvBinaryStrategy::class, 'ean');
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals("No such column", $e->getMessage());
        }
    }
}
