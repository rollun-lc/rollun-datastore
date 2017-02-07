<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 14.01.17
 * Time: 10:55 AM
 */

namespace rollun\datastore\Rql;


use rollun\datastore\Rql\Node\GroupbyNode;
use Xiag\Rql\Parser\AbstractNode;
use Xiag\Rql\Parser\DataType\Glob;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\LikeNode;
use Xiag\Rql\Parser\QueryBuilder;

class RqlQueryBuilder extends QueryBuilder
{
    public function __construct()
    {
        $this->query = new RqlQuery();
    }

    public function addNode(AbstractNode $node)
    {
        if ($node instanceof GroupbyNode) {
            return $this->query->setGroupby($node);
        }
        return parent::addNode($node);
    }

}