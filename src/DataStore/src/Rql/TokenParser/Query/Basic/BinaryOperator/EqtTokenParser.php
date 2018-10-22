<?php

namespace rollun\datastore\Rql\TokenParser\Query\Basic\BinaryOperator;

use rollun\datastore\Rql\Node\BinaryNode\EqtNode;

class EqtTokenParser extends BinaryTokenParserAbstract
{
    public function getOperatorName()
    {
        return 'isTrue';
    }

    protected function createNode(string $field)
    {
        return new EqtNode($field);
    }
}
