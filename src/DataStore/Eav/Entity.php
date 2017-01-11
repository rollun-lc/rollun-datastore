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
use Xiag\Rql\Parser\Query;
use rollun\datastore\DataStore\ConditionBuilder\SqlConditionBuilder;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\DbTable;
use rollun\datastore\Rql\Node\AggregateFunctionNode;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Predicate\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\TableGateway\TableGateway;

/**
 *
 * Add to config:
 * <code>
 *     'dataStore' => [
 *         'SomeResourceName' => [
 *             'class' => Entitiy::class,
 *             'tableName' => 'table_some_resource_name'
 *         ],
 *     ],
 * </code>
 *
 * Tablet 'able_some_resource_name' must be exist. Add code to  Eav\installer for create it.
 *
 */
class Entity extends DbTable
{

    public function getEntityName()
    {
        $tableName = $this->dbTable->table;
        return SysEntities::getEntityName($tableName);
    }

    public function getEntityTableName()
    {
        return $tableName = $this->dbTable->table;
    }


    protected function genItemProps(&$itemData)
    {
        /** @var Adapter $adapter */
        $adapter = $this->dbTable->getAdapter();

        $propsData = [];
        $props = [];
        foreach ($itemData as $key => $value) {
            if (strpos($key, SysEntities::PROP_PREFIX) === 0) {
                $propTableName = explode('.', $key)[0];
                $props[$key] = new Prop(new TableGateway($propTableName, $adapter));
                $propsData[$key] = $value;
                unset($itemData[$key]);
            }
        }
        return ['propsData' => $propsData, 'props' => $props];
    }

    protected function createProps($props, $propsData, &$itemInserted)
    {
        /** @var Adapter $adapter */
        $adapter = $this->dbTable->getAdapter();

        if (!empty($itemInserted)) {
            /**
             * @var string $key
             * @var Prop $prop
             */
            $identifier = $this->getIdentifier();
            foreach ($props as $key => $prop) {
                $itemInserted[$key] = $prop->createWithEntity(
                    $propsData[$key],
                    $itemInserted[$identifier],
                    $this->getEntityName(),
                    $key);
            }
        } else {
            throw new DataStoreException('Not all data has been inserted. -> rollback');
        }
    }

    protected function updateProps($props, $propsData, &$itemInserted)
    {
        $identifier = $this->getIdentifier();

        if (!empty($itemInserted)) {
            /**
             * @var string $key
             * @var  Prop $prop
             */
            foreach ($props as $key => $prop) {
                $propQuery = new Query();
                $propQuery->setQuery(
                    new EqNode($prop->getLinkedColumn($this->getEntityName(), $key), $itemInserted[$identifier]));
                $propQuery->setSelect(new SelectNode([$prop->getIdentifier()]));
                $allEntityProp = $prop->query($propQuery);

                foreach ($allEntityProp as $entityPropItem) {
                    $find = false;
                    foreach ($propsData[$key] as &$propDataItem) {
                        if (isset($propDataItem[$prop->getIdentifier()]) &&
                            $entityPropItem[$prop->getIdentifier()] === $propDataItem[$prop->getIdentifier()]
                        ) {
                            $find = true;
                            $diff = array_diff_assoc($entityPropItem, $propDataItem);
                            if (empty($diff) || count($propDataItem) == 1) {
                                unset($propDataItem);
                            }
                            break;
                        }
                    }
                    if (!$find) {
                        $prop->delete($entityPropItem[$prop->getIdentifier()]);
                    }
                }
                $prop->updateWithEntity($propsData[$key], $itemInserted[$identifier], $this->getEntityName(), $key);

                $propQuery->setSelect(new SelectNode());
                $allEntityProp = $prop->query($propQuery);
                $itemInserted[$key] = $allEntityProp;
            }
        }
    }


    public function create($itemData, $rewriteIfExist = false)
    {
        //Check props in $itemData filed and generate propsTableGateway
        //$this->propsTableGatewayInit($itemData);
        $adapter = $this->dbTable->getAdapter();
        $adapter->getDriver()->getConnection()->beginTransaction();

        $propsData = [];
        $props = [];
        extract($this->genItemProps($itemData));

        try {
            $sysEntities = new SysEntities(new TableGateway(SysEntities::TABLE_NAME, $adapter));
            $itemData = $sysEntities->prepareEntityCreate($this->getEntityName(), $itemData, $rewriteIfExist);

            $itemInserted = $this->_create($itemData, $rewriteIfExist);

            $this->createProps($props, $propsData, $itemInserted);
            $adapter->getDriver()->getConnection()->commit();
        } catch (\Exception $e) {
            $adapter->getDriver()->getConnection()->rollback();
            throw new DataStoreException('Can\'t insert item', 0, $e);
        }
        return $itemInserted;
    }

    public function update($itemData, $createIfAbsent = false)
    {
        $adapter = $this->dbTable->getAdapter();
        $adapter->getDriver()->getConnection()->beginTransaction();

        $propsData = [];
        $props = [];
        extract($this->genItemProps($itemData));
        try {
            if ($createIfAbsent) {
                throw new DataStoreException("This method dosn't work with flag $createIfAbsent = true");
            }

            $itemInserted = $this->_update($itemData, $createIfAbsent);

            $this->updateProps($props, $propsData, $itemInserted);
            $adapter->getDriver()->getConnection()->commit();
        } catch (\Exception $e) {
            $adapter->getDriver()->getConnection()->rollback();
            throw new DataStoreException('Can\'t update item', 0, $e);
        }
        return $itemInserted;
    }

    protected function _update($itemData, $createIfAbsent = false){
        $itemInserted = parent::_update($itemData, false);
        return $itemInserted;
    }

    protected function _create($itemData, $rewriteIfExist = false)
    {
        return parent::_create($itemData, false);
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
        $rowset = $adapter->query($sql, $adapter::QUERY_MODE_EXECUTE);

        $data = $rowset->toArray();
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

    protected function setSelectColumns(Select $selectSQL, Query $query)
    {
        $select = $query->getSelect();  //What fields will return
        $selectFields = !$select ? [] : $select->getFields();
        $props = [];
        if (!empty($selectFields)) {
            $fields = [];
            $hawAggregate = false;
            $hawProps = false;
            foreach ($selectFields as $field) {
                if ($field instanceof AggregateFunctionNode) {
                    $fildName = $field->__toString();
                    $fullFildName = $fildName == "count(id)" ? 'count(' . $this->dbTable->table . '.id)' : $fildName;
                    $fields[$field->getField() . "->" . $field->getFunction()] = new Expression($fullFildName);
                    $hawAggregate = true;
                } else if (strpos($field, SysEntities::PROP_PREFIX) === 0) {
                    $propTableName = explode('.', $field)[0];
                    $props[$field] = new Prop(new TableGateway($propTableName, $this->dbTable->getAdapter()));
                    $hawProps = true;
                } else {
                    $fields[] = $field;
                }
                if ($hawAggregate && $hawProps) {
                    throw new DataStoreException('Cannot use aggregate function with props');
                }
            }
            if (!empty($props)) {
                $fields['props'] = $props;
            }
            $selectSQL->columns($fields);
        }
        return $selectSQL;
    }

    protected function setSelectJoin(Select $selectSQL, Query $query)
    {
        $on = SysEntities::TABLE_NAME . '.' . $this->getIdentifier() . ' = ' . $this->getEntityTableName() . '.' . $this->getIdentifier();
        $selectSQL->join(
            SysEntities::TABLE_NAME
            , $on
            , Select::SQL_STAR, Select::JOIN_LEFT
        );
        return $selectSQL;
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

    public function getSqlQuery(Query $query)
    {
        $conditionBuilder = new SqlConditionBuilder($this->dbTable->getAdapter(), $this->dbTable->getTable());

        $selectSQL = $this->dbTable->getSql()->select();
        $selectSQL->where($conditionBuilder($query->getQuery()));
        $selectSQL = $this->setSelectOrder($selectSQL, $query);
        $selectSQL = $this->setSelectLimitOffset($selectSQL, $query);
        $selectSQL = $this->setSelectColumns($selectSQL, $query);

        $fields = $selectSQL->getRawState(Select::COLUMNS);
        if (isset($fields['props'])) {
            unset($fields['props']);
            $selectSQL->columns($fields);
        }

        $selectSQL = $this->setSelectJoin($selectSQL, $query);
        $selectSQL = $this->makeExternalSql($selectSQL);

        //build sql string
        $sql = $this->dbTable->getSql()->buildSqlString($selectSQL);
        //replace double ` char to single.
        $sql = str_replace(["`(", ")`", "``"], ['(', ')', "`"], $sql);
        return $sql;
    }

}
