<?php

namespace rollun\datastore\Rql\TokenParser\Query\Fiql\BinaryOperator;

use rollun\datastore\Rql\Node\BinaryNode\EqfNode;

class EqfNodeParser extends BinaryTokenParserAbstract
{
    public function getOperatorNames()
    {
        return ['isFalse'];
    }

    protected function createNode(string $field)
    {
        return new EqfNode($field);
    }
}
