<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Rql\TokenParser\Query\Basic\BinaryOperator;

use rollun\datastore\Rql\Node\BinaryNode\EqnNode;

class EqnTokenParser extends BinaryTokenParserAbstract
{
    protected function getOperatorName()
    {
        return 'eqn';
    }

    protected function createNode(string $field)
    {
        return new EqnNode($field);
    }
}
