<?php

namespace rollun\datastore\Rql\Node\BinaryNode;

class EqtNode extends BinaryOperatorNodeAbstract
{
    public function getNodeName()
    {
        return 'isTrue';
    }
}
