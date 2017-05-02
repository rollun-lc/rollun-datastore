<?php

/**
 * Created by PhpStorm.
 * User: root
 * Date: 14.07.16
 * Time: 14:53
 */
namespace rollun\datastore\Rql\TokenParser\Query\Fiql\ScalarOperator;

use rollun\datastore\Rql\Node\ContainsNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\LikeNode;
use Xiag\Rql\Parser\TokenParser\Query\Fiql\AbstractScalarOperatorTokenParser;

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
