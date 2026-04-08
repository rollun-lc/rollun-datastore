<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\intagration\DataStore;

use rollun\datastore\DataStore\CsvIntId;
use rollun\datastore\DataStore\DataStoreAbstract;

class CsvIntIdTest extends CsvBaseTest
{
    protected function createObject(): DataStoreAbstract
    {
        return new CsvIntId($this->filename, $this->delimiter);
    }

    protected function identifierToType($id)
    {
        return (int) $id;
    }
}
