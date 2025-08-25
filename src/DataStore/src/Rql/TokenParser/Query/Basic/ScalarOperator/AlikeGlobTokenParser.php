<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Rql\TokenParser\Query\Basic\ScalarOperator;

use rollun\datastore\Rql\Node\AlikeGlobNode;
use rollun\datastore\Rql\TokenParser\Support\AbstractScalarOperatorTokenParser;

class AlikeGlobTokenParser extends AbstractScalarOperatorTokenParser
{
    /**
     * @inheritdoc
     */
    protected function getOperatorName()
    {
        return 'alike';
    }

    /**
     * @inheritdoc
     */
    protected function createNode($field, $value)
    {
        return new AlikeGlobNode($field, $value);
    }
}
