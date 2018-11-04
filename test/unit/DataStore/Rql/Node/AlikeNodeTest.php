<?php

namespace test\unit\DataStore\Rql\Node;

use PHPUnit\Framework\TestCase;
use rollun\datastore\Rql\Node\AlikeNode;

class AlikeNodeTest extends TestCase
{
    protected function createObject($field, $value)
    {
        return new AlikeNode($field, $value);
    }

    public function dataProvider()
    {
        return [
            ['fieldName', 'fieldValue']
        ];
    }

    /**
     * @dataProvider dataProvider
     * @param $field
     * @param $value
     */
    public function testConstruct($field, $value)
    {
        $object = $this->createObject($field, $value);

        $this->assertEquals($value, $object->getValue());
        $this->assertEquals($field, $object->getField());
    }

    public function testSetters()
    {
        $field = 'field1';
        $value = 'value1';

        $object = $this->createObject('field2', 'value2');
        $object->setValue($value);
        $object->setField($field);

        $this->assertEquals($value, $object->getValue());
        $this->assertEquals($field, $object->getField());
    }

    /**
     * @dataProvider dataProvider
     * @param $field
     * @param $value
     */
    public function testGetNodeName($field, $value)
    {
        $object = $this->createObject($field, $value);
        $this->assertEquals($object->getNodeName(), 'alike');
    }
}
