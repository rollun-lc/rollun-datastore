<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\unit\DataStore\DataStore;

use PHPUnit\Framework\TestCase;

class CsvBaseCommaTest extends CsvBaseTestCase
{
    protected function getDelimiter(): string
    {
        return ',';
    }
}
