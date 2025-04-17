<?php

namespace rollun\test\unit\DataStore\DataStore;

use PHPUnit\Framework\TestCase;
use rollun\datastore\DataStore\CsvBase;

class CsvBaseLineBreakTest extends TestCase
{
    public function testCsvBaseGetAll(): void
    {
        $this->markTestIncomplete('Should be fixed');
        $this->assertEquals(
            (new CsvBase(
                __DIR__ . '/../../../../data/test/unit/DataStore/DataStore/CsvBase/with_newline.csv', ',',
            ))->getAll(),
            (new CsvBase(
                __DIR__ . '/../../../../data/test/unit/DataStore/DataStore/CsvBase/without_newline.csv', ',',
            ))->getAll(),
        );
    }
}