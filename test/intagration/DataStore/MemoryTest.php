<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\intagration\DataStore;

use rollun\datastore\DataStore\DataStoreAbstract;
use rollun\datastore\DataStore\Memory;

class MemoryTest extends BaseDataStoreTest
{
    protected function createObject(): DataStoreAbstract
    {
        return new Memory($this->getColumns());
    }
}
