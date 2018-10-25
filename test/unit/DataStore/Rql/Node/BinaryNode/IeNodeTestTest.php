<?php

namespace test\unit\DataStore\Rql\Node\BinaryNode;

use rollun\datastore\Rql\Node\BinaryNode\IeNode;

class IeNodeTestTest extends BinaryOperatorNodeAbstractTest
{
    public function createObject($field)
    {
        $this->object = new IeNode($field);
    }

    public function getNodeName(): string
    {
        return 'ie';
    }
}
