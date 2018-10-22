<?php

namespace rollun\datastore\Rql\Node\BinaryNode;

class EqfNode extends BinaryOperatorNodeAbstract
{
    public function getNodeName()
    {
        return 'isFalse';
    }
}
