<?php

namespace rollun\datastore\Rql\TokenParser\Query\Basic\BinaryOperator;

use rollun\datastore\Rql\Node\BinaryNode\EqtNode;

class EqtTokenParser extends BinaryTokenParserAbstract
{
    protected function getOperatorName()
    {
        return 'eqt';
    }

    protected function createNode(string $field)
    {
        return new EqtNode($field);
    }
}
