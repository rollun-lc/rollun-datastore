<?php

namespace rollun\datastore\Rql\TokenParser\Query\Basic\BinaryOperator;

use rollun\datastore\Rql\Node\BinaryNode\IeNode;

class IeTokenParser extends BinaryTokenParserAbstract
{
    public function getOperatorName()
    {
        return 'ie';
    }

    public function createNode(string $field)
    {
        return new IeNode($field);
    }
}
