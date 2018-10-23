<?php

namespace rollun\datastore\Rql\TokenParser\Query\Fiql\ScalarOperator;

use rollun\datastore\Rql\Node\AlikeNode;
use Xiag\Rql\Parser\TokenParser\Query\Fiql\AbstractScalarOperatorTokenParser;

class AlikeTokenParser extends AbstractScalarOperatorTokenParser
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
        return new AlikeNode($field, $value);
    }
}
