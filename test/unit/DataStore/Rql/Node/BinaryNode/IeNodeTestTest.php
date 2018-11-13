<?php

namespace rollun\test\unit\DataStore\Rql\Node\BinaryNode;

use PHPUnit\Framework\TestCase;
use rollun\datastore\Rql\Node\BinaryNode\IeNode;

class IeNodeTestTest extends TestCase
{
    protected function createObject($field)
    {
        return new IeNode($field);
    }

    public function dataProvider()
    {
        return [
            ['fieldName']
        ];
    }

    public function testSetField()
    {
        $field = 'fieldName1';
        $object = $this->createObject('fieldName2');
        $object->setField($field);
        $this->assertAttributeEquals($field, 'field', $object);
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

    /**
     * @dataProvider dataProvider
     * @param $field
     */
    public function testGetNodeName($field)
    {
        $object = $this->createObject($field);
        $this->assertEquals($object->getNodeName(), 'ie');
    }
}
