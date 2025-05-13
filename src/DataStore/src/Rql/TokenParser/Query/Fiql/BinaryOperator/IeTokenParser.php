<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

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
