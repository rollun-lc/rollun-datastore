<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Rql;

use rollun\datastore\Rql\Node\GroupByNode;
use Xiag\Rql\Parser\AbstractNode;
use Xiag\Rql\Parser\QueryBuilder;

class RqlQueryBuilder extends QueryBuilder
{
    public function __construct()
    {
        $this->query = new RqlQuery();
    }

    public function addNode(AbstractNode $node)
    {
        if ($node instanceof GroupByNode) {
            return $this->query->setGroupBy($node);
        }
        return parent::addNode($node);
    }
}
