<?php

namespace rollun\datastore\Rql\TokenParser\Query\Fiql\BinaryOperator;

use rollun\datastore\Rql\Node\BinaryNode\EqnNode;

class EqnTokenParser extends BinaryTokenParserAbstract
{
    public function getOperatorNames()
    {
        return ['eqn'];
    }

    protected function createNode(string $field)
    {
        return new EqnNode($field);
    }
}
