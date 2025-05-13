<?php

namespace rollun\test\unit\DataStore\DataStore\Csv\LineBreak;

use PHPUnit\Framework\TestCase;
use rollun\datastore\DataStore\CsvBase;

class CsvBaseLineBreakTest extends TestCase
{
    public function testReturnsSameRecords(): void
    {
        $withNewLine = new CsvBase(__DIR__ . '/with_newline.csv', ',');
        $withoutNewLine = new CsvBase(__DIR__ . '/without_newline.csv', ',');

        $this->assertEquals($withNewLine->getAll(), $withoutNewLine->getAll());
    }
}
