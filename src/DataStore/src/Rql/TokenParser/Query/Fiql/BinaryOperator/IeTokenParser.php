<?php

namespace rollun\datastore\Rql\TokenParser\Query\Fiql\BinaryOperator;

use rollun\datastore\Rql\Node\BinaryNode\IeNode;

class IeTokenParser extends BinaryTokenParserAbstract
{
    public function getOperatorNames()
    {
        return ['ie'];
    }

    public function createNode(string $field)
    {
        return new IeNode($field);
    }
}
