<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\TableGateway;

use rollun\datastore\DataStore\ConditionBuilder\SqlConditionBuilder;
use rollun\datastore\DataStore\ConnectionException;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\Interfaces\ReadInterface;
use rollun\datastore\Rql\Node\AggregateFunctionNode;
use rollun\datastore\Rql\RqlQuery;
use Xiag\Rql\Parser\Query;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Sql\Predicate\Expression;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Sql;

class SqlQueryBuilder
{
    /**
     * @var AdapterInterface
     */
    private $adapter;

    /**
     * @var string
     */
    private $tableName;

    /**
     * @var SqlConditionBuilder
     */
    protected $sqlConditionBuilder;

    /**
     * SqlQueryBuilder constructor.
     * @param AdapterInterface $adapter
     * @param $tableName
     * @param SqlConditionBuilder|null $sqlConditionBuilder
     */
    public function __construct(AdapterInterface $adapter, $tableName, SqlConditionBuilder $sqlConditionBuilder = null)
    {
        $this->adapter = $adapter;
        $this->tableName = $tableName;

        if ($this->sqlConditionBuilder === null) {
            $this->sqlConditionBuilder = new SqlConditionBuilder($this->adapter, $this->tableName);
        } else {
            $this->sqlConditionBuilder = $sqlConditionBuilder;
        }
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

        $orders = [];

        foreach ($sort->getFields() as $field => $direction) {
            if ($direction == 1 || strtolower($direction) == 'asc') {
                $orders[$field] = Select::ORDER_ASCENDING;
            } else {
                $orders[$field] = Select::ORDER_DESCENDING;
            }
        }

        $selectSQL->order($orders);

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
            $sql = new Sql($this->adapter);

            /** @var Select $selectSql */
            $selectSql = $sql->select($this->tableName);

            // Create select with where conditions
            // Can throw connection exception, because it does sql string escaping which require connection to
            // get table charset
            $selectSql->where($this->sqlConditionBuilder->__invoke($query->getQuery()));

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
            $sql = $sql->buildSqlString($selectSql);

            // Replace double ` char to single.
            $sql = str_replace(["`(", ")`", "``"], ['(', ')', "`"], $sql);

            return $sql;
        } catch (\Throwable $throwable) {
            if ($throwable instanceof ConnectionException) {
                throw $throwable;
            }
            throw new DataStoreException("Can't build sql from rql query", 0, $throwable);
        }
    }
}
