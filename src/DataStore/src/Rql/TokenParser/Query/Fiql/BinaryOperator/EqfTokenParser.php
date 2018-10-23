<?php

namespace rollun\datastore\Rql\TokenParser\Query\Fiql\BinaryOperator;

use rollun\datastore\Rql\Node\BinaryNode\EqfNode;

class EqfTokenParser extends BinaryTokenParserAbstract
{
    public function getOperatorNames()
    {
        return ['eqf'];
    }

    protected function createNode(string $field)
    {
        return new EqfNode($field);
    }
}
