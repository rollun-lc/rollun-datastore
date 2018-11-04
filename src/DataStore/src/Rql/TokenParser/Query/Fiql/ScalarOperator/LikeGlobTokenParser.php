<?php

/**
 * Created by PhpStorm.
 * User: root
 * Date: 14.07.16
 * Time: 14:53
 */
namespace rollun\datastore\Rql\TokenParser\Query\Fiql\ScalarOperator;

use rollun\datastore\Rql\Node\LikeGlobNode;
use Xiag\Rql\Parser\TokenParser\Query\Fiql\AbstractScalarOperatorTokenParser;

class LikeGlobTokenParser extends AbstractScalarOperatorTokenParser
{
    /**
     * @inheritdoc
     */
    protected function getOperatorNames()
    {
        return ['like'];
    }

    /**
     * @inheritdoc
     */
    protected function createNode($field, $value)
    {
        return new LikeGlobNode($field, $value);
    }
}
