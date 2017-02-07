<?php

/**
 * Created by PhpStorm.
 * User: root
 * Date: 14.07.16
 * Time: 14:53
 */
namespace rollun\datastore\Rql\TokenParser\Query\Fiql\ScalarOperator;

use rollun\datastore\Rql\Node\ContainsNode;
use rollun\datastore\Rql\Node\LikeGlobNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\LikeNode;
use Xiag\Rql\Parser\TokenParser\Query\Basic\AbstractScalarOperatorTokenParser;

class LikeGlobTokenParser extends AbstractScalarOperatorTokenParser
{
    /**
     * @inheritdoc
     */
    protected function getOperatorName()
    {
        return 'like';
    }

    /**
     * @inheritdoc
     */
    protected function createNode($field, $value)
    {
        return new LikeGlobNode($field, $value);
    }
}
