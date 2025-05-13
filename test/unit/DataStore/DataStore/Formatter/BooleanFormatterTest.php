<?php

declare(strict_types=1);

namespace rollun\test\unit\DataStore\DataStore\Formatter;

use rollun\datastore\DataStore\Formatter\BooleanFormatter;
use rollun\datastore\DataStore\Formatter\FormatterInterface;

class BooleanFormatterTest extends TestCase
{
    public function dataProvider(): array
    {
        return [
            'Convert true string' => ['false', false],
            'Convert false string' => ['true', true],
            'Convert empty string' => ['', false],
            'Convert not empty string' => ['something', true],
            'Convert zero int' => [0, false],
            'Convert non-zero int' => [random_int(1, 100), true],
            'Convert zero float' => [0, false],
            'Convert non-zero float' => [0.01, true],
        ];
    }

    protected function getFormatter(): FormatterInterface
    {
        return new BooleanFormatter();
    }
}
