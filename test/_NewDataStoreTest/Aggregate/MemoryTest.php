<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 12.04.18
 * Time: 2:25 PM
 */

namespace rollun\test\datastore\DataStore\Aggregate;


use rollun\datastore\DataStore\Memory;
use rollun\test\datastore\DataStore\NewStyleAggregateDecoratorTrait;
use rollun\test\datastore\DataStore\OldStyleAggregateDecoratorTrait;

class MemoryTest extends AbstractAggregateTest
{
    use NewStyleAggregateDecoratorTrait;
    use AggregateDataProviderTrait;

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