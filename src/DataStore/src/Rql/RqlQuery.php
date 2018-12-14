<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Rql;

use rollun\datastore\Rql\Node\GroupbyNode;
use Xiag\Rql\Parser\Query;

class RqlQuery extends Query
{

    /** @var  GroupbyNode */
    protected $groupBy;

    /**
     * Query constructor. Init query with rql string or another query obj.
     * @param $query
     */
    public function __construct($query = null)
    {
        if (is_string($query)) {
            /** @var RqlQuery $query */
            $query = RqlParser::rqlDecode($query);
        }
        if ($query instanceof Query) {
            $this->query = $query->query;
            $this->sort = $query->sort;
            $this->limit = $query->limit;
            $this->select = $query->select;
        }
        if ($query instanceof RqlQuery) {
            $this->groupBy = $query->groupBy;
        }
    }

    /**
     * @param mixed $groupBy
     * @return RqlQuery
     */
    public function setGroupBy(GroupbyNode $groupBy)
    {
        $this->groupBy = $groupBy;
        return $this;
    }

    /**
     * @return GroupbyNode
     */
    public function getGroupBy()
    {
        return $this->groupBy;
    }
}
