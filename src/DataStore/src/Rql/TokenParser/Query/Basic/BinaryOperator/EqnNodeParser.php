<?php

namespace rollun\datastore\Rql\TokenParser\Query\Basic\BinaryOperator;

use rollun\datastore\Rql\Node\BinaryNode\EqnNode;

class EqnNodeParser extends BinaryTokenParserAbstract
{
    public function getOperatorName()
    {
        return 'isNull';
    }

    protected function createNode(string $field)
    {
        return new EqnNode($field);
    }
}
