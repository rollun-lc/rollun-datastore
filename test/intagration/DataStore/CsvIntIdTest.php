<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\intagration\DataStore;

class CsvIntIdTest extends CsvBaseTest
{
    protected function identifierToType($id)
    {
        return (int) $id;
    }
}
