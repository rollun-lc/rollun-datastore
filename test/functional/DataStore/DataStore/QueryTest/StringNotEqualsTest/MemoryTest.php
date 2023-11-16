<?php

namespace rollun\test\functional\DataStore\DataStore\QueryTest\StringNotEqualsTest;

use rollun\datastore\DataStore\Interfaces\DataStoreInterface;
use rollun\datastore\DataStore\Memory;

class MemoryTest extends BaseTest
{
    /**
     * @var Memory
     */
    private $dataStore;

    protected function getDataStore(): DataStoreInterface
    {
        if ($this->dataStore === null) {
            $this->dataStore = new Memory([self::ID_NAME, self::FIELD_NAME]);
        }
        return $this->dataStore;
    }
}
