<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 15.04.18
 * Time: 1:44 PM
 */

namespace rollun\test\datastore\DataStore;


use rollun\datastore\DataStore\Memory;

abstract class AbstractMemoryTest extends AbstractDataStoreTest
{
    /**
     * Prepare datastore for initialized with transmitted data
     * @param array $data
     * @return void
     */
    protected function setInitialData(array $data)
    {
        $testCaseName = $this->getTestCaseName();
        $this->setConfigForTest($testCaseName,["initialData" => $data]);
    }

    /**
     * Prepare
     */
    public function setUp()
    {
        $name = $this->getName(false);
        $initialData = $this->getConfigForTest($name)["initialData"];
        //create store
        $this->object = new Memory();

        //create data
        foreach ($initialData as $datum) {
            $this->object->create($datum);
        }
    }

    /**
     * Return dataStore Identifier field name
     * @return string
     */
    protected function getDataStoreIdentifier()
    {
        return Memory::DEF_ID;
    }
}