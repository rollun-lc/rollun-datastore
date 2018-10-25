<?php

namespace test\unit\DataStore\Rql\Node\BinaryNode;

use rollun\datastore\Rql\Node\BinaryNode\EqtNode;

class EqtNodeTestTest extends BinaryOperatorNodeAbstractTest
{
    public function createObject($field)
    {
        $this->object = new EqtNode($field);
    }

    public function getNodeName(): string
    {
        return 'eqt';
    }
}
