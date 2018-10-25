<?php

namespace test\unit\DataStore\Rql\Node;

use PHPUnit\Framework\TestCase;
use rollun\datastore\Rql\Node\LikeGlobNode;
use Xiag\Rql\Parser\DataType\Glob;

class LikeGlobNodeTest extends TestCase
{
    protected function createObject($field, $value)
    {
        return new LikeGlobNode($field, $value);
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
        $glob = new Glob($value);
        $object = $this->createObject($field, $value);

        $this->assertEquals($glob, $object->getValue());
        $this->assertEquals($field, $object->getField());

        $object = $this->createObject($field, $glob);

        $this->assertEquals($glob, $object->getValue());
        $this->assertEquals($field, $object->getField());
    }

    public function testSetters()
    {
        $field = 'field1';
        $value = 'value1';
        $glob = new Glob($value);

        $object = $this->createObject('field2', 'value2');
        $object->setValue($value);
        $object->setField($field);

        $this->assertEquals($glob, $object->getValue());
        $this->assertEquals($field, $object->getField());

        $object->setValue($glob);

        $this->assertEquals($glob, $object->getValue());
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
        $this->assertEquals($object->getNodeName(), 'like');
    }
}
