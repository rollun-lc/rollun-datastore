<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

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
