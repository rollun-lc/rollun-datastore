<?php

/**
 * Created by PhpStorm.
 * User: root
 * Date: 14.07.16
 * Time: 14:53
 */
namespace rolluncom\datastore\Rql\TokenParser\Query\Basic\ScalarOperator;

use Xiag\Rql\Parser\Node\Query\ScalarOperator\LikeNode;
use Xiag\Rql\Parser\TokenParser\Query\Basic\AbstractScalarOperatorTokenParser;

class MatchTokenParser extends AbstractScalarOperatorTokenParser
{
    /**
     * @inheritdoc
     */
    protected function getOperatorName()
    {
        return 'match';
    }

    /**
     * @inheritdoc
     */
    protected function createNode($field, $value)
    {
        return new LikeNode($field, $value);
    }
}
