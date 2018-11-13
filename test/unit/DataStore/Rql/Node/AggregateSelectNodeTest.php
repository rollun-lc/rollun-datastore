<?php

namespace rollun\test\unit\DataStore\Rql\Node;

use PHPUnit\Framework\TestCase;
use rollun\datastore\Rql\Node\AggregateSelectNode;

class AggregateSelectNodeTest extends TestCase
{
    protected function createObject(array $fields = [])
    {
        return new AggregateSelectNode($fields);
    }

    public function dataProvider()
    {
        return [
            [
                [
                    'fieldName1' => 'direction1',
                    'fieldName2' => 'direction2',
                ],
            ]
        ];
    }

    /**
     * @dataProvider dataProvider
     * @param $fields
     */
    public function testConstruct($fields)
    {
        $object = $this->createObject($fields);
        $this->assertEquals($object->getFields(), $fields);
    }

    public function testGetNodeName()
    {
        $object = $this->createObject();
        $this->assertEquals($object->getNodeName(), 'select');
    }

    public function testAddFields()
    {
        $fields = [
            'fieldName1' => 'direction1',
            'fieldName2' => 'direction2',
        ];
        $object = $this->createObject($fields);
        $direction = 'direction3';
        $field = 'fieldName3';
        $fields[$field] = $direction;
        $object->addField($field, $direction);
        $this->assertEquals($object->getFields(), $fields);
    }
}
