<?php

namespace rollun\test\unit\DataStore\Rql\Node\BinaryNode;

use PHPUnit\Framework\TestCase;
use rollun\datastore\Rql\Node\BinaryNode\EqnNode;

class EqnNodeTestTest extends TestCase
{
    protected function createObject($field)
    {
        return new EqnNode($field);
    }

    public function dataProvider()
    {
        return [
            ['fieldName'],
        ];
    }

    /**
     * @dataProvider dataProvider
     * @param $field
     */
    public function testConstruct($field)
    {
        $object = $this->createObject($field);
        $this->assertEquals($field, $object->getField());
    }

    public function testSetField()
    {
        $field = 'fieldName1';
        $object = $this->createObject('fieldName2');
        $object->setField($field);
        //$this->assertAttributeEquals($field, 'field', $object);
        $reflection = new \ReflectionProperty($object, 'field');
        $reflection->setAccessible(true);
        $this->assertEquals($field, $reflection->getValue($object));
    }

    /**
     * @dataProvider dataProvider
     * @param $field
     */
    public function testGetNodeName($field)
    {
        $object = $this->createObject($field);
        $this->assertEquals($object->getNodeName(), 'eqn');
    }
}
