<?php

namespace rollun\test\unit\Files\CsvFileObject;

use rollun\files\Csv\CsvFileObject;
use rollun\test\unit\Files\FilesAbstractTest;

class CsvFileObjectTest extends FilesAbstractTest
{
    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        // set error handler
        set_error_handler(
            function ($errno, $errstr) {
                if (0 === error_reporting()) {
                    return false;
                }
                throw new \Exception($errstr, 500);
            }
        );
    }

    /**
     * @inheritDoc
     */
    protected function tearDown()
    {
        parent::tearDown();

        // restore error handler
        restore_error_handler();
    }

    public function getRows()
    {
        return [
            [0, "A"],
            [1, "B"],
            [2, "C\nD"],
            [3, "E\nF"],
            [4, "G\n\"123\"H"],
            [5, "I\n\"123\"J\nK\""],
        ];
    }

    public function testAddRow()
    {
        $expectedRows = $this->getRows();
        $fullFilename = $this->makeFullFileName();
        @unlink($fullFilename);
        CsvFileObject::createNewCsvFile($fullFilename, ["id", "val"]);
        $csvFileObject = new CsvFileObject($fullFilename);
        foreach ($expectedRows as $row) {
            $csvFileObject->addRow($row);
            $actual = $csvFileObject->getRow($row[0]);
            $this->assertEquals($expectedRows[$row[0]], $actual);
        }
        return $csvFileObject;
    }

    public function getColumnsProvider()
    {
        //$columsStrings
        return array(
            ["val\n"],
            ["val"],
            ["id,val\n"],
            ["id,val"],
            ["val\nA\n", ['A']],
            ["val\nA", ['A']],
            ["id,val\n1,A", ['1', 'A']],
            ["id,val\n0123,AB CD", ['0123', 'AB CD']],
        );
    }

    /**
     * @dataProvider getColumnsProvider
     */
    public function testGetColumns($columsStrings)
    {
        $csvFileObject = $this->getCsvFileObject($columsStrings);
        $expected = explode("\n", $columsStrings)[0];
        $actual = implode(',', $csvFileObject->getColumns());
        $this->assertEquals($expected, $actual);
    }

    public function getRowProvider()
    {
        //$columsStrings
        return array(
            ["val\nA\n", ['A']],
            ["val\nA", ['A']],
            ["id,val\n1,A", ['1', 'A']],
            ["id,val\n0123,AB CD", ['0123', 'AB CD']],
        );
    }

    /**
     * @dataProvider getRowProvider
     */
    public function testGetRow($stringInFile, $arrayExpected)
    {
        $csvFileObject = $this->getCsvFileObject($stringInFile);
        $arrayActual = $csvFileObject->getRow(0);
        $this->assertEquals($arrayExpected, $arrayActual);
    }

    public function createNewCsvFileProvider()
    {
        //$columsArray
        return array(
            [["val"]],
            [["id", "val"]],
        );
    }

    /**
     * @dataProvider createNewCsvFileProvider
     */
    public function testCreateNewCsvFile($columsArray)
    {

        $fullFilename = $this->makeFullFileName();
        @unlink($fullFilename);
        CsvFileObject::createNewCsvFile($fullFilename, $columsArray);
        $arrayExpected = $columsArray;
        $csvFileObject = new CsvFileObject($fullFilename);
        $arrayActual = $csvFileObject->getColumns();
        $this->assertEquals($arrayExpected, $arrayActual);
    }

    /**
     *
     * @param CsvFileObject $csvFileObject
     *
     * @depends testAddRow
     */
    public function testIterator(CsvFileObject $csvFileObject)
    {
        $expected = array(
            [0, "A"],
            [1, "B"],
            [2, "C\nD"],
            [3, "E\nF"],
            [4, "G\n\"123\"H"],
            [5, "I\n\"123\"J\nK\""],
        );
        foreach ($csvFileObject as $value) {
            $actual[] = $value;
        }
        $this->assertEquals($expected, $actual);
        return $csvFileObject;
    }

    /**
     *
     * @param CsvFileObject $csvFileObject
     *
     * @depends testAddRow
     */
    public function testIteratorAndPosReferenceInFile(CsvFileObject $csvFileObject)
    {
        $expected = array(
            [0, "A"],
            [1, "B"],
            [2, "C\nD"],
            [3, "E\nF"],
            [4, "G\n\"123\"H"],
            [5, "I\n\"123\"J\nK\""],
        );
        foreach ($csvFileObject as $value) {
            $actual[] = $csvFileObject->getFileObject()->current();
        }
        $this->assertEquals($expected, $actual);
        return $csvFileObject;
    }

    public function testWithWrongCustomConfigs()
    {
        $fullFilename = $this->makeFullFileName();
        @unlink($fullFilename);
        try {
            CsvFileObject::createNewCsvFile($fullFilename, ['id', 'val'], '|', "'", '/');
        } catch (\Exception $exception) {
            $this->assertEquals("In writing mode, the escape char must be a backslash '\\'. The given escape char '/' will be ignored.", $exception->getMessage());
        }
    }

    public function testWithCustomConfigs()
    {
        $fullFilename = $this->makeFullFileName();
        @unlink($fullFilename);
        CsvFileObject::createNewCsvFile($fullFilename, ['id', 'val'], '|', "'");
        $csvFileObject = new CsvFileObject($fullFilename);
        $expectedRows = array(
            [0, "E\nF"],
            [1, "G\n\"123\"H"],
            [2, "I\n\"123\"J\nK\""],
        );
        foreach ($expectedRows as $row) {
            $csvFileObject->addRow($row);
            $actual = $csvFileObject->getRow($row[0]);
            $this->assertEquals($expectedRows[$row[0]], $actual);
        }
    }

    public function testReadRowsFromFileGeneratedByLibreOffice()
    {
        $expected = array(
            [0, "123\nSd \"123\" s, df\n4234234"],
            [1, "A"],
            [2, "B, C, D, “E”"],
            [3, "\"123\", 1, 321"],
        );
        $fullFilename = $this->makeFullFileName('CsvFileGeneratedByLibreOffice.csv');
        $csvFileObject = new CsvFileObject($fullFilename);
        foreach ($csvFileObject as $value) {
            $actual[] = $value;
        }
        $this->assertEquals($expected, $actual);
    }

    public function testReadRowsFromFileGeneratedByGoogleSpreadsheet()
    {
        $expected = array(
            [0, "A"],
            [1, "B\nC\n\"test message\"\n"],
            [2, "\"test\""],
            [3, "1, \"quotes\", 3"],
        );

        $fullFilename = $this->makeFullFileName('CsvFileGeneratedByGoogleSpreadsheet.csv');
        $csvFileObject = new CsvFileObject($fullFilename);
        foreach ($csvFileObject as $i => $value) {
            $this->assertTrue($expected[$i][0] == $value[0]);
        }
    }

    public function testReadRowsFromFileGeneratedByMsExcelMacintoshWithEncodingUtf8()
    {
        $expected = array(
            [0, "A"],
            [1, "123\nSd \"123\" s, df\n4234234"],
            [2, "B, \"C\", D"],
            [3, "\"E\""],
            [4, "\"F\", \"G\", \"I"],
            [5, "J"],
        );
        $fullFilename = $this->makeFullFileName('CsvFileGeneratedByMSExcelMacintoshUtf8.csv');
        $csvFileObject = new CsvFileObject($fullFilename, ';');

        foreach ($csvFileObject as $value) {
            $actual[] = $value;
        }
        $this->assertEquals($expected, $actual);
    }

    public function testReadRowsFromFileGeneratedByMsExcelMsDosThrowEncodingException()
    {
        $expected = array(
            [0, "A"],
            [1, "123\nSd \"123\" s, df\n4234234"],
            [2, "B, \"C\", D"],
            [3, "\"E\""],
            [4, "\"F\", \"G\", \"I"],
            [5, "J"],
        );
        $fullFilename = $this->makeFullFileName('CsvFileGeneratedByMsExcelMsDos.csv');
        $csvFileObject = new CsvFileObject($fullFilename, ';');

        foreach ($csvFileObject as $value) {
            $actual[] = $value;
        }
        $this->assertEquals($expected, $actual);
    }

    /**
     *
     * @param CsvFileObject $csvFileObject
     *
     * @depends testIterator
     */
    public function testDeleteAllRows(CsvFileObject $csvFileObject)
    {

        $actual = $csvFileObject->getRow(0)[1];
        $this->assertEquals('A', $actual);
        $csvFileObject->deleteAllRows();
        $this->expectException(\InvalidArgumentException::class);
        $csvFileObject->getRow(0);
    }

    public function testCreateCsvFileWithDataByRfc()
    {
        return $this->testAddRow();
    }

    /**
     * @depends testCreateCsvFileWithDataByRfc
     */
    public function testConvertingRfcFromatToExcel(CsvFileObject $csvFileObject)
    {
        $expected = array(
            [0, "A"],
            [1, "B"],
            [2, "C\nD"],
            [3, "E\nF"],
            [4, "G\n\"123\"H"],
            [5, "I\n\"123\"J\nK\""],
        );
        $fullFilename = $this->makeFullFileName('CsvFileLikeMSExcel.csv');
        @unlink($fullFilename);
        CsvFileObject::createNewCsvFile($fullFilename, ['id', 'val'], ";");
        $csvFileObjectExcel = new CsvFileObject($fullFilename, ";");
        $csvFileObject->getFileObject()->rewind();
        foreach ($csvFileObject as $row) {
            $csvFileObjectExcel->addRow($row);
            $actual = $csvFileObject->getRow($row[0]);
            $this->assertEquals($expected[$row[0]], $actual);
        }

        return $csvFileObjectExcel;
    }

    /**
     * @depends testConvertingRfcFromatToExcel
     */
    public function testConvertedExcelFromatFromRfc(CsvFileObject $csvFileObjectExcel)
    {
        $expected = array(
            [0, "A"],
            [1, "B"],
            [2, "C\nD"],
            [3, "E\nF"],
            [4, "G\n\"123\"H"],
            [5, "I\n\"123\"J\nK\""],
        );
        $csvFileObjectExcel->getFileObject()->rewind();
        foreach ($csvFileObjectExcel as $row) {
            $actual[] = $row;
        }
        $this->assertEquals($expected, $actual);
    }

    public function testReadCsvFileWithEmptyLines()
    {
        $expected = [
            [0, "123\nSd \"123\" s, df\n4234234"],
            [1, "A"],
            [3, "\"123\", 1, 321"],
        ];
        $fullFilename = $this->makeFullFileName('CsvFileWithEmptyLines.csv');
        $csvFileObject = new CsvFileObject($fullFilename);
        foreach ($csvFileObject as $i => $row) {
            $this->assertEquals($expected[$i], $row);
        }
    }

    protected function getCsvFileObject(string $stringInFile, array $rows = null)
    {
        $fullFilename = $this->makeFile($stringInFile);
        $csvFileObject = new CsvFileObject($fullFilename);
        if (is_null($rows)) {
            return $csvFileObject;
        }
    }
}
