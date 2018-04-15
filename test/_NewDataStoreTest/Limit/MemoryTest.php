<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 13.04.18
 * Time: 3:49 PM
 */

namespace rollun\test\datastore\DataStore\Limit;


use rollun\datastore\DataStore\Memory;

class MemoryTest extends AbstractLimitTest
{
    use LimitDataProviderTrait;

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