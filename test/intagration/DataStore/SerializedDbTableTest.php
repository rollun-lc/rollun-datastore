<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\intagration\DataStore;

use rollun\datastore\DataStore\DataStoreAbstract;
use rollun\datastore\DataStore\SerializedDbTable;
use rollun\datastore\TableGateway\SqlQueryBuilder;

class SerializedDbTableTest extends DbTableTest
{
    public function createObject(): DataStoreAbstract
    {
        return new SerializedDbTable($this->tableGateway);
    }
}
