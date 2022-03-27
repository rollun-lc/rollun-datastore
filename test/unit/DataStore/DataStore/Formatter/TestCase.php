<?php

declare(strict_types=1);

namespace rollun\test\unit\DataStore\DataStore\Formatter;

use rollun\datastore\DataStore\Formatter\FormatterInterface;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    abstract public function dataProvider(): array;

    /**
     * @dataProvider dataProvider
     */
    public function test($value, $expected)
    {
        $result = $this->getFormatter()->format($value);
        self::assertEquals($expected, $result);
    }

    abstract protected function getFormatter(): FormatterInterface;
}