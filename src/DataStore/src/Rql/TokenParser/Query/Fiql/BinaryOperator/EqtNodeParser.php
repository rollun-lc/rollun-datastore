<?php

namespace rollun\datastore\Rql\TokenParser\Query\Fiql\BinaryOperator;

use rollun\datastore\Rql\Node\BinaryNode\EqtNode;

class EqtNodeParser extends BinaryTokenParserAbstract
{
    public function getOperatorNames()
    {
        return ['isTrue'];
    }

    protected function createNode(string $field)
    {
        return new EqtNode($field);
    }
}
