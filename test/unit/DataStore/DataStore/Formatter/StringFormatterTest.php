<?php

declare(strict_types=1);

namespace rollun\test\unit\DataStore\DataStore\Formatter;

use rollun\datastore\DataStore\Formatter\FormatterInterface;
use rollun\datastore\DataStore\Formatter\StringFormatter;

class StringFormatterTest extends TestCase
{
    public function dataProvider(): array
    {
        return [
            'Convert string' => [$str = uniqid(), $str],
            'Convert int' => [123, '123'],
            'Convert float' => [12.345, '12.345'],
            'Convert bool false' => [false, ''],
            'Convert bool true' => [true, '1'],
            'Convert associative array' => [['key' => 'value'], '{"key":"value"}'],
            'Convert list array' => [['str', 123, 1.23], '["str",123,1.23]'],
        ];
    }

    protected function getFormatter(): FormatterInterface
    {
        return new StringFormatter();
    }
}
