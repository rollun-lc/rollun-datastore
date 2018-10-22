<?php

namespace rollun\datastore\Rql\Node\BinaryNode;

class IsNullNode extends BinaryOperatorNodeAbstract
{
    public function getNodeName()
    {
        return 'isNull';
    }
}
