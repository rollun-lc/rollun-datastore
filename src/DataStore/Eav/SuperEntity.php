<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace rollun\datastore\DataStore\Eav;

use Xiag\Rql\Parser\Node\Query\ScalarOperator\EqNode;
use Xiag\Rql\Parser\Node\SelectNode;
use Xiag\Rql\Parser\Node\SortNode;
use Xiag\Rql\Parser\Query;
use rollun\datastore\DataStore\ConditionBuilder\SqlConditionBuilder;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\DbTable;
use rollun\datastore\DataStore\Interfaces\SqlQueryGetterInterface;
use rollun\datastore\Rql\Node\AggregateFunctionNode;
use rollun\datastore\TableGateway\TableManagerMysql;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Metadata\Source\Factory;
use Zend\Db\Sql\Ddl\Column\Column;
use Zend\Db\Sql\Predicate\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\TableGateway\TableGateway;

class SuperEntity extends Entity implements SqlQueryGetterInterface
{

    const INNER_JOIN = '-'; //'-'

    /**
     *
     * @var array [$dataStoreObj1, '`', $dataStoreObj1, '`'...]
     */

    protected $joinedEntities;

    public function __construct(TableGateway $dbTable, $joinedEntities)
    {
        //$dbTable - TableGateway for SysEntities table
        parent::__construct($dbTable);
        $this->joinedEntities = $joinedEntities;
    }

    public function getEntityName()
    {
        $name = "";
        /** @var Entity $entity */
        foreach ($this->joinedEntities as $entity) {
            if (is_object($entity)) {
                $name .= $entity->getEntityName();
            } else {
                $name .= $entity;
            }
        }
        rtrim($name, SuperEntity::INNER_JOIN);
        return $name;
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function query(Query $query)
    {

        $selectSQL = $this->dbTable->getSql()->select();
        $selectSQL = $this->setSelectColumns($selectSQL, $query);

        $fields = $selectSQL->getRawState(Select::COLUMNS);
        $props = [];
        if (isset($fields['props'])) {
            $props = $fields['props'];
            unset($fields['props']);
            $selectSQL->columns($fields);
        }

        $sql = $this->getSqlQuery($query);

        /** @var Adapter $adapter */
        $adapter = $this->dbTable->getAdapter();
        $rowSet = $adapter->query($sql, $adapter::QUERY_MODE_EXECUTE);

        $data = $rowSet->toArray();
        if (!empty($props)) {
            foreach ($data as &$item) {
                /** @var $prop Prop */
                foreach ($props as $key => $prop) {
                    $linkedColumn = $prop->getLinkedColumn($this->getEntityName(), $key);
                    $propQuery = new Query();
                    $propQuery->setQuery(new EqNode($linkedColumn, $item[$this->getIdentifier()]));
                    $propData = $prop->query($propQuery);
                    $item[$key] = $propData;
                }
            }
        }
        return $data;
    }

    protected function joinedEntitiesItemHandler($itemData, callable $handler)
    {
        /** @var Adapter $adapter */
        $adapter = $this->dbTable->getAdapter();

        $itemInserted = [];
        /** @var Entity $entity */
        foreach ($this->joinedEntities as $entity) {
            if (is_object($entity)) {

                $entityItem = [];

                $mysqlManager = new TableManagerMysql($adapter);
                $columnsNames = $mysqlManager->getColumnsNames($entity->getEntityTableName());

                foreach($columnsNames as $columnName){
                    if (isset($itemData[$columnName])) {
                        $entityItem[$columnName] = $itemData[$columnName];
                    }
                }

                $entityItem = $handler($entity, $entityItem);
                $itemInserted = array_merge($itemInserted, $entityItem);
            }
        }

        return $itemInserted;
    }

    protected function _create($itemData, $rewriteIfExist = false)
    {
        $itemInserted = $this->joinedEntitiesItemHandler($itemData, function (DbTable $entity, $entityItem) {
            return $entity->_create($entityItem);
        });
        return $itemInserted;
    }

    protected function _update($itemData, $createIfAbsent = false)
    {
        $identifier = $this->getIdentifier();
        $itemInserted = $this->joinedEntitiesItemHandler($itemData, function (DbTable $entity, $entityItem) use ($identifier) {
            if (count($entityItem) > 1) {
                $entityItem = $entity->_update($entityItem);
            } else {
                $entityItem = $entity->read($entityItem[$identifier]);
            }
            return $entityItem;
        });
        return $itemInserted;
    }

    public function delete($id)
    {
        $sysEntities = new SysEntities(new TableGateway(SysEntities::TABLE_NAME, $this->dbTable->getAdapter()));
        return $sysEntities->delete($id);
    }

    public function deleteAll()
    {
        $sysEntities = new SysEntities(new TableGateway(SysEntities::TABLE_NAME, $this->dbTable->getAdapter()));
        return $sysEntities->deleteAllInEntity($this->getEntityName());
    }

    protected function setSelectColumns(Select $selectSQL, Query $query)
    {
        $select = $query->getSelect();
        $selectField = !$select ? [] : $select->getFields();

        /** @var Adapter $adapter */
        $adapter = $this->dbTable->getAdapter();
        $metadata = Factory::createSourceFromAdapter($adapter);
        $sysEntityTable = $metadata->getTable(SysEntities::TABLE_NAME);
        if (!empty($selectField)) {
            $sysEntitiesFields = [];
            $hawAggregate = false;
            $hawProps = false;
            foreach ($selectField as $field) {
                if ($field instanceof AggregateFunctionNode) {
                    if (in_array($field->getField(), $sysEntityTable->getColumns())) {

                        $fildName = $field->__toString();
                        $fullFildName = $fildName == "count(id)" ? 'count(' . $this->dbTable->table . '.id)' : $fildName;
                        $sysEntitiesFields[$field->getField() .
                                "->" . $field->getFunction()] = new Expression($fullFildName);
                        $hawAggregate = true;
                    }
                } else if (strpos($field, SysEntities::PROP_PREFIX) === 0) {
                    $propTableName = explode('.', $field)[0];
                    $props[$field] = new Prop(new TableGateway($propTableName, $this->dbTable->getAdapter()));
                    $hawProps = true;
                } else {
                    if (in_array($field, $sysEntityTable->getColumns())) {
                        $sysEntitiesFields[] = $field;
                    }
                }
                if ($hawAggregate && $hawProps) {
                    throw new DataStoreException('Cannot use aggregate function with props');
                }
            }
            if (!empty($props)) {
                $sysEntitiesFields['props'] = $props;
            }
            $selectSQL->columns($sysEntitiesFields);
        }
        return $selectSQL;
    }

    protected function setSelectJoin(Select $selectSQL, Query $query)
    {

        $select = $query->getSelect();
        $selectField = !$select ? [] : $select->getFields();

        /** @var Adapter $adapter */
        $adapter = $this->dbTable->getAdapter();
        $metadata = Factory::createSourceFromAdapter($adapter);
        $sysEntityTable = $metadata->getTable(SysEntities::TABLE_NAME);
        $identifier = $this->getIdentifier();

        //todo: agregate function
        $joinedEntityfileds = [];
        if (!empty($selectField)) {
            foreach ($selectField as $field) {
                if ($field instanceof AggregateFunctionNode and ! in_array($field->getField(), $sysEntityTable->getColumns())) {
                    $joinedEntityfileds[$field->getField() .
                            "->" . $field->getFunction()] = new Expression($field->__toString());
                } else if (!in_array($field, $sysEntityTable->getColumns())) {
                    $joinedEntityfileds[] = $field;
                }
            }
        }

        $prew = $this;
        /** @var DbTable $entity */
        foreach ($this->joinedEntities as $entity) {
            if (is_object($entity)) {
                $entityField = [];
                $entityTable = $metadata->getTable($entity->dbTable->table);
                /** @var Column $column */
                foreach ($entityTable->getColumns() as $column) {
                    $colName = $column->getName();
                    if (in_array($colName, $joinedEntityfileds)) {
                        $entityField[] = $colName;
                    }
                }
                $selectSQL->join(
                        $entity->dbTable->table, $entity->dbTable->table . '.' . $identifier . '=' . $prew->dbTable->table . '.' . $identifier, empty($entityField) ? Select::SQL_STAR : $entityField, Select::JOIN_INNER
                );
                $prew = $entity;
            }
        }
        return $selectSQL;
    }

    protected function setSelectOrder(Select $selectSQL, Query $query)
    {
        $sort = $query->getSort();
        $sortFields = !$sort ? [$this->dbTable->table . '.' . $this->getIdentifier() => SortNode::SORT_ASC] : $sort->getFields();

        /** @var Adapter $adapter */
        $adapter = $this->dbTable->getAdapter();
        $metadata = Factory::createSourceFromAdapter($adapter);

        foreach ($sortFields as $ordKey => $ordVal) {
            if (!preg_match('/[\w]+\.[\w]+/', $ordKey)) {
                $fined = false;
                /** @var DbTable $entity */
                foreach ($this->joinedEntities as $entity) {
                    if (is_object($entity)) {
                        $entityTable = $metadata->getTable($entity->dbTable->table);
                        /** @var Column $column */
                        foreach ($entityTable->getColumns() as $column) {
                            if ($column->getName() == $ordKey) {
                                $ordKey = $entity->dbTable->table . '.' . $ordKey;
                                $fined = true;
                                break;
                            }
                        }
                    }
                    if ($fined) {
                        break;
                    }
                }
                if (!$fined) {
                    $ordKey = $this->dbTable->table . '.' . $ordKey;
                }
            }
            if ((int) $ordVal === SortNode::SORT_DESC) {
                $selectSQL->order($ordKey . ' ' . Select::ORDER_DESCENDING);
            } else {
                $selectSQL->order($ordKey . ' ' . Select::ORDER_ASCENDING);
            }
        }
        return $selectSQL;
    }

}
