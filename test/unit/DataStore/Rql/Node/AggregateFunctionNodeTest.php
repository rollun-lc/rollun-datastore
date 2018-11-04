<?php

namespace test\unit\DataStore\Rql\Node;

use PHPUnit\Framework\TestCase;
use rollun\datastore\Rql\Node\AggregateFunctionNode;

class AggregateFunctionNodeTest extends TestCase
{
    protected function createObject($function, $field)
    {
        return new AggregateFunctionNode($function, $field);
    }

    public function dataProvider()
    {
        return [
            ['functionName', 'fieldName']
        ];
    }

    /**
     * @dataProvider dataProvider
     * @param $function
     * @param $field
     */
    public function testConstruct($function, $field)
    {
        $object = $this->createObject($function, $field);
        $this->assertEquals($object->getFunction(), $function);
        $this->assertEquals($object->getField(), $field);
    }

    /**
     * @dataProvider dataProvider
     * @param $function
     * @param $field
     */
    public function testGetNodeName($function, $field)
    {
        $object = $this->createObject($function, $field);
        $this->assertEquals($object->getNodeName(), $function);
    }

    /**
     * @dataProvider dataProvider
     * @param $function
     * @param $field
     */
    public function testToString($function, $field)
    {
        $object = $this->createObject($function, $field);
        $this->assertEquals($object->__toString(), "$function($field)");
    }
}
