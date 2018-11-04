<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 26.11.16
 * Time: 11:43 AM
 */

namespace rollun\datastore\Rql;

use rollun\datastore\Rql\Node\GroupByNode;
use Xiag\Rql\Parser\Query;

class RqlQuery extends Query
{

    /** @var  GroupByNode */
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
    public function setGroupBy(GroupByNode $groupBy)
    {
        $this->groupBy = $groupBy;
        return $this;
    }

    /**
     * @return GroupByNode
     */
    public function getGroupBy()
    {
        return $this->groupBy;
    }


}