<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace rollun\datastore\DataStore;

use rollun\datastore\DataStore\ConditionBuilder\SqlConditionBuilder;
use rollun\datastore\DataStore\Interfaces\SqlQueryGetterInterface;
use rollun\datastore\Rql\Node\AggregateFunctionNode;
use rollun\datastore\Rql\Node\AggregateSelectNode;
use rollun\datastore\Rql\RqlQuery;
use Xiag\Rql\Parser\Node\SortNode;
use Xiag\Rql\Parser\Query;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Predicate\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\TableGateway\TableGateway;

/**
 * DataStores as Db Table
 *
 * @todo rearrangement query. Use TableGateway method instead string manipulation for compatible
 * @uses zend-db
 * @see https://github.com/zendframework/zend-db
 * @see http://en.wikipedia.org/wiki/Create,_read,_update_and_delete
 * @category   rest
 * @package    zaboy
 */
class DbTable extends DataStoreAbstract implements SqlQueryGetterInterface
{

    /**
     *
     * @var TableGateway
     */
    protected $dbTable;

    /**
     *
     * @param TableGateway $dbTable
     */
    public function __construct(TableGateway $dbTable)
    {
        $this->dbTable = $dbTable;
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function create($itemData, $rewriteIfExist = false)
    {

        $adapter = $this->dbTable->getAdapter();

        $adapter->getDriver()->getConnection()->beginTransaction();
        try {
            $insertedItem = $this->_create($itemData, $rewriteIfExist);
            $adapter->getDriver()->getConnection()->commit();
        } catch (\Throwable $e) {
            $adapter->getDriver()->getConnection()->rollback();
            throw new DataStoreException('Can\'t insert item ' . $e->getMessage(), 0, $e);
        }
        return $insertedItem;
    }

    /**
     * @param $itemData
     * @param bool $rewriteIfExist
     * @return array|mixed|null
     */
    protected function _create($itemData, $rewriteIfExist = false)
    {

        $identifier = $this->getIdentifier();
        if ($rewriteIfExist) {
            if (isset($itemData[$identifier])) {
                $this->deleteIfExist($itemData, $identifier);
            } else if (isset($itemData[0]) && isset($itemData[0][$identifier])) {
                foreach ($itemData as $item) {
                    $this->deleteIfExist($item, $identifier);
                }
            }
        }
        $this->dbTable->insert($itemData);
        if (!isset($itemData[$identifier])) {
            $id = $this->dbTable->getLastInsertValue();
            $newItem = $this->read($id);
        } else {
            $newItem = $itemData;
        }

        return $newItem;
    }

    /**
     * @param array $item
     * @param $identifier
     * @throws DataStoreException
     */
    protected function deleteIfExist(array $item, $identifier)
    {
        if ($this->read($item[$identifier])) {
            try {
                $this->dbTable->delete(array($identifier => $item[$identifier]));
            } catch (\Throwable $e) {
                throw new DataStoreException("Can't delete item with '$identifier' = " . $item[$identifier], 0, $e);
            }
        }
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function query(Query $query)
    {
        /** @var Adapter $adapter */
        $adapter = $this->dbTable->getAdapter();
        $sql = $this->getSqlQuery($query);
        try {
            $rowset = $adapter->query($sql, $adapter::QUERY_MODE_EXECUTE);
        } catch (\PDOException $exception) {
            throw new DataStoreException("Error by execute $sql query to {$this->getDbTable()->getTable()}.", 500, $exception);
        }

        return $rowset->toArray();
    }

    /**
     * @param Select $selectSQL
     * @param Query $query
     * @return Select
     */
    protected function setSelectLimitOffset(Select $selectSQL, Query $query)
    {
        $limits = $query->getLimit();
        $limit = !$limits ? self::LIMIT_INFINITY : $limits->getLimit();
        $offset = !$limits ? 0 : $limits->getOffset();
        if ($limit <> self::LIMIT_INFINITY) {
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
        if (!$sort) {
            $sort = new SortNode();
            if ($query->getSelect()) {
                foreach ($query->getSelect()->getFields() as $field) {
                    if (is_string($field)) {
                        $sort->addField($field);
                    }
                }
            }
        }
        $sortFields = $sort->getFields();

        foreach ($sortFields as $ordKey => $ordVal) {
            if (!preg_match('/[\w]+\.[\w]+/', $ordKey)) {
                $ordKey = $this->dbTable->table . '.' . $ordKey;
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
                    //$fields[$field->getField() . "->" . $field->getFunction()] = new Expression($field->__toString());
                    $fields[$field->__toString()."`"] = new Expression($field->__toString());
                } else {
                    $fields[] = $field;
                }
            }
            $selectSQL->columns($fields,false);
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
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function update($itemData, $createIfAbsent = false)
    {

        /*$adapter = $this->dbTable->getAdapter();
        $adapter->getDriver()->getConnection()->beginTransaction();

        $identifier = $this->getIdentifier();
        if (!isset($itemData[$identifier])) {
            throw new DataStoreException('Item must has primary key');
        }
        $id = $itemData[$identifier];
        $this->checkIdentifierType($id);

        $errorMsg = 'Can\'t update item with "id" = ' . $id;

        $queryStr = 'SELECT ' . Select::SQL_STAR
            . ' FROM ' . $adapter->platform->quoteIdentifier($this->dbTable->getTable())
            . ' WHERE ' . $adapter->platform->quoteIdentifier($identifier) . ' = ?'
            . ' FOR UPDATE';

        try {
            //is row with this index exist?
            $rowset = $adapter->query($queryStr, array($id));
            $isExist = !is_null($rowset->current());
            switch (true) {
                case!$isExist && !$createIfAbsent:
                    throw new DataStoreException($errorMsg);
                case!$isExist && $createIfAbsent:
                    $this->dbTable->insert($itemData);
                    $result = $itemData;
                    break;
                case $isExist:
                    unset($itemData[$identifier]);
                    $this->dbTable->update($itemData, array($identifier => $id));
                    $rowset = $adapter->query($queryStr, array($id));
                    $result = $rowset->current()->getArrayCopy();
                    break;
            }
            $adapter->getDriver()->getConnection()->commit();
        } catch (\Exception $e) {
            $adapter->getDriver()->getConnection()->rollback();
            throw new DataStoreException($errorMsg, 0, $e);
        }
        return $result;*/

        $adapter = $this->dbTable->getAdapter();
        $adapter->getDriver()->getConnection()->beginTransaction();
        try {
            $result = $this->_update($itemData, $createIfAbsent);
            $adapter->getDriver()->getConnection()->commit();
        } catch (\Throwable $e) {
            $adapter->getDriver()->getConnection()->rollback();
            throw new DataStoreException('Can\'t update item', 0, $e);
        }
        return $result;
    }

    protected function _update($itemData, $createIfAbsent = false)
    {
        $adapter = $this->dbTable->getAdapter();

        $identifier = $this->getIdentifier();
        if (!isset($itemData[$identifier])) {
            throw new DataStoreException('Item must has primary key');
        }
        $id = $itemData[$identifier];
        $this->checkIdentifierType($id);

        $queryStr = 'SELECT ' . Select::SQL_STAR
            . ' FROM ' . $adapter->platform->quoteIdentifier($this->dbTable->getTable())
            . ' WHERE ' . $adapter->platform->quoteIdentifier($identifier) . ' = ?'
            . ' FOR UPDATE';

        //is row with this index exist?
        $rowset = $adapter->query($queryStr, array($id));
        $isExist = !is_null($rowset->current());
        $result = [];
        switch (true) {
            case!$isExist && !$createIfAbsent:
                throw new DataStoreException('Can\'t update item with "id" = ' . $id);
            case!$isExist && $createIfAbsent:
                $this->dbTable->insert($itemData);
                $result = $itemData;
                break;
            case $isExist:
                unset($itemData[$identifier]);
                $this->dbTable->update($itemData, array($identifier => $id));
                $rowset = $adapter->query($queryStr, array($id));
                $result = $rowset->current()->getArrayCopy();
                break;
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function delete($id)
    {
        $identifier = $this->getIdentifier();
        $this->checkIdentifierType($id);

        $element = $this->read($id);

        $deletedItemsCount = $this->dbTable->delete(array($identifier => $id));
        return $element;
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function read($id)
    {
        $this->checkIdentifierType($id);
        $identifier = $this->getIdentifier();
        $rowset = $this->dbTable->select(array($identifier => $id));
        $row = $rowset->current();
        if (isset($row)) {
            return $row->getArrayCopy();
        } else {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function deleteAll()
    {
        $where = '1=1';
        $deletedItemsCount = $this->dbTable->delete($where);
        return $deletedItemsCount;
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function count()
    {
        /** @var Adapter $adapter */
        $adapter = $this->dbTable->getAdapter();
        /* @var $rowset ResultSet */
        $rowset = $adapter->query(
            'SELECT COUNT(*) AS count FROM '
            . $adapter->platform->quoteIdentifier($this->dbTable->getTable())
            , $adapter::QUERY_MODE_EXECUTE);
        return $rowset->current()['count'];
    }

    /**
     * @return TableGateway
     */
    public function getDbTable()
    {
        return $this->dbTable;
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    protected function getKeys()
    {
        $identifier = $this->getIdentifier();
        $select = $this->dbTable->getSql()->select();
        $select->columns(array($identifier));
        $rowset = $this->dbTable->selectWith($select);
        $keysArrays = $rowset->toArray();
        if (PHP_VERSION_ID >= 50500) {
            $keys = array_column($keysArrays, $identifier);
        } else {
            $keys = array();
            foreach ($keysArrays as $value) {
                $keys[] = $value[$identifier];
            }
        }
        return $keys;
    }

    /**
     * @param Query $query
     * @return mixed|string
     */
    public function getSqlQuery(Query $query)
    {
        $conditionBuilder = new SqlConditionBuilder($this->dbTable->getAdapter(), $this->dbTable->getTable());

        $selectSQL = $this->dbTable->getSql()->select();
        $selectSQL->where($conditionBuilder($query->getQuery()));
        $selectSQL = $this->setSelectOrder($selectSQL, $query);
        $selectSQL = $this->setSelectLimitOffset($selectSQL, $query);
        $selectSQL = ($query instanceof RqlQuery && $query->getGroupby() != null) ?
            $this->setGroupby($selectSQL, $query) : $selectSQL;
        $selectSQL = $this->setSelectColumns($selectSQL, $query);
        $selectSQL = $this->setSelectJoin($selectSQL, $query);

        //build sql string
        $sql = $this->dbTable->getSql()->buildSqlString($selectSQL);
        //replace double ` char to single.
        $sql = str_replace(["`(", ")`", "``"], ['(', ')', "`"], $sql);
        return $sql;
    }
}
