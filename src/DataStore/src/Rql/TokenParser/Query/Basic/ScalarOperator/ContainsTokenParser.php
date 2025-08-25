<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Rql\TokenParser\Query\Basic\ScalarOperator;

use rollun\datastore\Rql\Node\ContainsNode;
use rollun\datastore\Rql\TokenParser\Support\AbstractScalarOperatorTokenParser;

class ContainsTokenParser extends AbstractScalarOperatorTokenParser
{
    /**
     * @inheritdoc
     */
    protected function getOperatorName()
    {
        return 'contains';
    }

    /**
     * @inheritdoc
     */
    protected function createNode($field, $value)
    {
        return new ContainsNode($field, $value);
    }
}
