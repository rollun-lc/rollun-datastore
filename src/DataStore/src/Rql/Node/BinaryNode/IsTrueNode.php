<?php

namespace rollun\datastore\Rql\Node\BinaryNode;

class IsTrueNode extends BinaryOperatorNodeAbstract
{
    public function getNodeName()
    {
        return 'isTrue';
    }
}
