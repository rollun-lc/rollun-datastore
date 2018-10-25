<?php

namespace test\unit\DataStore\Rql\Node\BinaryNode;

use PHPUnit\Framework\TestCase;
use rollun\datastore\Rql\Node\BinaryNode\BinaryOperatorNodeAbstract;

abstract class BinaryOperatorNodeAbstractTest extends TestCase
{
    /**
     * @var BinaryOperatorNodeAbstract
     */
    protected $object;

    abstract public function createObject($field);

    abstract public function getNodeName(): string;

    public function testField()
    {
        $field = 'a';
        $this->createObject($field);
        $this->object->setField($field);
        $this->assertEquals($field, $this->object->getField());
    }

    public function testConstruct()
    {
        $field = 'a';
        $this->createObject($field);
        $this->assertEquals($field, $this->object->getField());
    }

    public function testGetNodeName()
    {
        $field = 'a';
        $this->createObject($field);
        $this->assertEquals($this->object->getNodeName(), $this->getNodeName());
    }
}
