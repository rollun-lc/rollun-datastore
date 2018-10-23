<?php

namespace rollun\datastore\Rql\Node\BinaryNode;

class EqnNode extends BinaryOperatorNodeAbstract
{
    public function getNodeName()
    {
        return 'eqn';
    }
}
