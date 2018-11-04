<?php

namespace test\unit\DataStore\Rql\Node\BinaryNode;

use PHPUnit\Framework\TestCase;
use rollun\datastore\Rql\Node\BinaryNode\EqtNode;

class EqtNodeTestTest extends TestCase
{
    protected function createObject($field)
    {
        return new EqtNode($field);
    }

    public function dataProvider()
    {
        return [
            ['fieldName']
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
        $this->assertAttributeEquals($field, 'field', $object);
    }

    /**
     * @dataProvider dataProvider
     * @param $field
     */
    public function testGetNodeName($field)
    {
        $object = $this->createObject($field);
        $this->assertEquals($object->getNodeName(), 'eqt');
    }
}
