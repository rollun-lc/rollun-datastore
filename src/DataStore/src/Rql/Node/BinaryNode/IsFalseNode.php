<?php

namespace rollun\datastore\Rql\Node\BinaryNode;

class IsFalseNode extends BinaryOperatorNodeAbstract
{
    public function getNodeName()
    {
        return 'isFalse';
    }
}
