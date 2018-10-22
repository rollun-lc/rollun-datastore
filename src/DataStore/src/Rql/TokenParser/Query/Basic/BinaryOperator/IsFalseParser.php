<?php

namespace rollun\datastore\Rql\TokenParser\Query\Basic\BinaryOperator;

use rollun\datastore\Rql\Node\BinaryNode\IsFalseNode;

class IsFalseParser extends BinaryTokenParserAbstract
{
    public function getOperatorName()
    {
        return 'isFalse';
    }

    protected function createNode(string $field)
    {
        return new IsFalseNode($field);
    }
}
