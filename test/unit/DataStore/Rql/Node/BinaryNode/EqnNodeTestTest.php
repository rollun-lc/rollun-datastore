<?php

namespace test\unit\DataStore\Rql\Node\BinaryNode;

use rollun\datastore\Rql\Node\BinaryNode\EqnNode;

class EqnNodeTestTest extends BinaryOperatorNodeAbstractTest
{
    public function createObject($field)
    {
        $this->object = new EqnNode($field);
    }

    public function getNodeName(): string
    {
        return 'eqn';
    }
}
