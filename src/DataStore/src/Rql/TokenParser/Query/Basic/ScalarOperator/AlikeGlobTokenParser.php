<?php

namespace rollun\datastore\Rql\TokenParser\Query\Basic\ScalarOperator;

use rollun\datastore\Rql\Node\AlikeGlobNode;
use Xiag\Rql\Parser\TokenParser\Query\Fiql\AbstractScalarOperatorTokenParser;

class AlikeGlobTokenParser extends AbstractScalarOperatorTokenParser
{
    /**
     * @inheritdoc
     */
    protected function getOperatorNames()
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
