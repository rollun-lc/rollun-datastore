<?php

namespace rollun\datastore\Rql\TokenParser\Query\Fiql\ScalarOperator;

use rollun\datastore\Rql\Node\AlikeGlobNode;
use Xiag\Rql\Parser\TokenParser\Query\Basic\AbstractScalarOperatorTokenParser;

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
