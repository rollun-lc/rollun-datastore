<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\TableGateway;

use rollun\datastore\DataStore\ConditionBuilder\MysqlConditionBuilder;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\Interfaces\ReadInterface;
use rollun\datastore\Rql\Node\AggregateFunctionNode;
use rollun\datastore\Rql\RqlQuery;
use Xiag\Rql\Parser\Node\SortNode;
use Xiag\Rql\Parser\Query;
use Zend\Db\Adapter\Platform\Mysql;
use Zend\Db\Sql\Platform\AbstractPlatform;
use Zend\Db\Sql\Predicate\Expression;
use Zend\Db\Sql\Select;

class SqlQueryBuilder
{
    /**
     * @var AbstractPlatform
     */
    private $platform;

    /**
     * @var string
     */
    private $tableName;

    /**
     * @var MysqlConditionBuilder
     */
    protected $sqlConditionBuilder;

    /**
     * SqlQueryBuilder constructor.
     * @param AbstractPlatform $platform
     * @param $tableName
     * @param MysqlConditionBuilder|null $sqlConditionBuilder
     */
    public function __construct(AbstractPlatform $platform, $tableName, MysqlConditionBuilder $sqlConditionBuilder = null)
    {
        $this->platform = $platform;
        $this->tableName = $tableName;
        $this->sqlConditionBuilder = $sqlConditionBuilder;
    }

    protected function getSqlConditionBuilder()
    {
        if ($this->sqlConditionBuilder === null) {
            return new MysqlConditionBuilder(new Mysql(), $this->tableName);
        }

        return $this->sqlConditionBuilder;
    }

    /**
     * @param Select $selectSQL
     * @param Query $query
     * @return Select
     */
    protected function setSelectLimitOffset(Select $selectSQL, Query $query)
    {
        $limits = $query->getLimit();
        $limit = !$limits ? ReadInterface::LIMIT_INFINITY : $limits->getLimit();
        $offset = !$limits ? 0 : $limits->getOffset();

        if ($limit <> ReadInterface::LIMIT_INFINITY) {
            $selectSQL->limit($limit);
        }

        if ($offset <> 0) {
            $selectSQL->offset($offset);
        }

        return $selectSQL;
    }

    /**
     * @param Select $selectSQL
     * @param Query $query
     * @return Select
     */
    protected function setSelectOrder(Select $selectSQL, Query $query)
    {
        $sort = $query->getSort();

        if (!$sort || empty($sort->getFields())) {
            return $selectSQL;
        }

        $sortFields = $sort->getFields();

        foreach ($sortFields as $ordKey => $ordVal) {
            if (!preg_match('/[\w]+\.[\w]+/', $ordKey)) {
                $ordKey = $this->tableName . '.' . $ordKey;
            }
            if ((int)$ordVal === SortNode::SORT_DESC) {
                $selectSQL->order($ordKey . ' ' . Select::ORDER_DESCENDING);
            } else {
                $selectSQL->order($ordKey . ' ' . Select::ORDER_ASCENDING);
            }
        }

        return $selectSQL;
    }

    /**
     * @param Select $selectSQL
     * @param Query $query
     * @return Select
     */
    protected function setSelectColumns(Select $selectSQL, Query $query)
    {
        $select = $query->getSelect();  //What fields will return
        $selectFields = !$select ? [] : $select->getFields();

        if (!empty($selectFields)) {
            $fields = [];

            foreach ($selectFields as $field) {
                if ($field instanceof AggregateFunctionNode) {
                    $fields[$field->__toString() . "`"] = new Expression($field->__toString());
                } else {
                    $fields[] = $field;
                }
            }

            $selectSQL->columns($fields, false);
        }

        return $selectSQL;
    }

    /**
     * @param Select $selectSQL
     * @param RqlQuery $query
     * @return Select
     */
    protected function setGroupby(Select $selectSQL, RqlQuery $query)
    {
        $selectSQL->group($query->getGroupby()->getFields());
        return $selectSQL;
    }

    /**
     * @param Query $query
     * @return string
     * @throws DataStoreException
     */
    public function buildSql(Query $query): string
    {
        try {
            $selectSql = new Select($this->tableName);

            // Create select with where conditions
            $selectSql->where($this->getSqlConditionBuilder()->__invoke($query->getQuery()));

            // Set order
            $selectSql = $this->setSelectOrder($selectSql, $query);

            // Set limit
            $selectSql = $this->setSelectLimitOffset($selectSql, $query);

            // Set group by
            if ($query instanceof RqlQuery && $query->getGroupby() != null) {
                $selectSql = $this->setGroupby($selectSql, $query);
            }

            $selectSql = $this->setSelectColumns($selectSql, $query);

            // Create sql query
            $sql = $this->platform->setSubject($selectSql)->getSqlString();

            // Replace double ` char to single.
            $sql = str_replace(["`(", ")`", "``"], ['(', ')', "`"], $sql);

            return $sql;
        } catch (\Throwable $throwable) {
            throw new DataStoreException("Can't build sql from rql query", 0, $throwable);
        }
    }
}
