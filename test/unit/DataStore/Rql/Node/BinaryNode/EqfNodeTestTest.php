<?php

namespace test\unit\DataStore\Rql\Node\BinaryNode;

use rollun\datastore\Rql\Node\BinaryNode\EqfNode;

class EqfNodeTestTest extends BinaryOperatorNodeAbstractTest
{
    public function createObject($field)
    {
        $this->object = new EqfNode($field);
    }

    public function getNodeName(): string
    {
        return 'eqf';
    }
}
