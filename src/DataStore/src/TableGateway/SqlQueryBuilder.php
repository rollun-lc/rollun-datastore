<?php


namespace rollun\datastore\TableGateway;


use rollun\datastore\DataStore\ConditionBuilder\SqlConditionBuilder;
use rollun\datastore\DataStore\Interfaces\ReadInterface;
use rollun\datastore\Rql\Node\AggregateFunctionNode;
use rollun\datastore\Rql\RqlQuery;
use Xiag\Rql\Parser\Node\SortNode;
use Xiag\Rql\Parser\Query;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Predicate\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;

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
     * SqlQueryBuilder constructor.
     * @param AdapterInterface $adapter
     * @param $tableName
     */
    public function __construct(AdapterInterface $adapter, $tableName)
    {

        $this->adapter = $adapter;
        $this->tableName = $tableName;
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
     * @param Query $query
     * @return Select
     */
    protected function setSelectJoin(Select $selectSQL, Query $query)
    {
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
     * @throws SqlBuildException
     */
    public function buildSql(Query $query): string {
        try {
            $conditionBuilder = new SqlConditionBuilder($this->adapter, $this->tableName);

            $sql = new Sql($this->adapter, $this->tableName);
            $selectSQL = $sql->select();
            $selectSQL->where($conditionBuilder($query->getQuery()));
            $selectSQL = $this->setSelectOrder($selectSQL, $query);
            $selectSQL = $this->setSelectLimitOffset($selectSQL, $query);
            $selectSQL = ($query instanceof RqlQuery && $query->getGroupby() != null) ?
                $this->setGroupby($selectSQL, $query) : $selectSQL;
            $selectSQL = $this->setSelectColumns($selectSQL, $query);
            $selectSQL = $this->setSelectJoin($selectSQL, $query);

            //build sql string
            $sqlString = $sql->buildSqlString($selectSQL);
            //replace double ` char to single.
            $sqlString = str_replace(["`(", ")`", "``"], ['(', ')', "`"], $sqlString);
            return $sqlString;
        } catch (\Throwable $throwable) {
            throw new SqlBuildException("");
        }
    }
}