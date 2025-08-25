<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Rql\TokenParser\Query\Fiql\ScalarOperator;

use rollun\datastore\Rql\Node\ContainsNode;
use rollun\datastore\Rql\TokenParser\Support\Fiql\AbstractScalarOperatorTokenParser;

class ContainsTokenParser extends AbstractScalarOperatorTokenParser
{
    /**
     * @inheritdoc
     */
    protected function getOperatorNames()
    {
        return ['contains'];
    }

    /**
     * @inheritdoc
     */
    protected function createNode($field, $value)
    {
        return new ContainsNode($field, $value);
    }
}
