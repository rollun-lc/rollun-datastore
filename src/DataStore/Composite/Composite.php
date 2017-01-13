<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 28.10.16
 * Time: 12:19 PM
 */

namespace rollun\datastore\DataStore\Composite;


use Xiag\Rql\Parser\Node\Query\ScalarOperator\EqNode;
use Xiag\Rql\Parser\Node\SelectNode;
use Xiag\Rql\Parser\Query;
use rollun\datastore\DataStore\ConditionBuilder\SqlConditionBuilder;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\DbTable;
use rollun\datastore\Rql\Node\AggregateFunctionNode;
use rollun\datastore\TableGateway\TableManagerMysql;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Metadata\Source\Factory;
use Zend\Db\Sql\Select;
use Zend\Db\TableGateway\TableGateway;

class Composite extends DbTable
{
    const DB_SERVICE_NAME = 'composite db';
    /**
     * @var array
     * [
     *      'single' => [],
     *      'multiple' => []
     * ]
     */
    protected $boundTables;

    public function query(Query $query)
    {
        $selectSQL = $this->dbTable->getSql()->select();
        $selectSQL = $this->setSelectColumns($selectSQL, $query);

        $fields = $selectSQL->getRawState(Select::COLUMNS);
        $bounds = [];
        if (isset($fields['.bounds.'])) {
            $bounds = $fields['.bounds.'];
            unset($fields);
        }


        $sql = $this->getSqlQuery($query);
        /** @var Adapter $adapter */
        $adapter = $this->dbTable->getAdapter();
        $rowset = $adapter->query($sql, $adapter::QUERY_MODE_EXECUTE);

        $data = $rowset->toArray();

        //todo: change call place

        if (!empty($bounds)) {
            //todo: change call place
            $this->initBound();
            foreach ($data as &$item) {
                foreach ($bounds as $bound) {
                    /** [$name => $query] */
                    $name = array_keys($bound)[0];
                    $boundQuery = $bound[$name];
                    if (isset($this->boundTables['multiple'][$name])) {
                        /** @var Composite $composite */
                        $composite = $this->boundTables['multiple'][$name]['table'];
                        $boundQuery->setQuery(new EqNode(
                            $this->boundTables['multiple'][$name]['column'],
                            $item[$this->getIdentifier()]
                        ));
                        $result = $composite->query($boundQuery);
                        if (isset($item[$name])) {
                            $result = array_merge_recursive($item[$name], $result);
                        }
                        $item[$name] = $result;
                    } else if (isset($this->boundTables['single'][$name])) {
                        /** @var Composite $composite */
                        $composite = $this->boundTables['single'][$name]['table'];

                        $boundQuery->setQuery(new EqNode(
                            $this->boundTables['single'][$name]['column'],
                            $item[$this->boundTables['single'][$name]['myColumn']]
                        ));

                        $result = $composite->query($boundQuery);

                        if (isset($result[0])) {
                            $result = $result[0];
                            unset($result[$this->getIdentifier()]);
                            foreach ($result as $key => $value){
                                unset($result[$key]);
                                $result[$name . "." . $key] = $value;
                            }
                            $itemMarge = array_merge_recursive($item, $result);
                            foreach ($itemMarge as $key => $value) {
                                $item[$key] = $value;
                            }
                        }
                    } else {
                        throw new DataStoreException('Bounds not found');
                    }
                }
            }
        }

        return $data;
    }

    protected function setSelectColumns(Select $selectSQL, Query $query)
    {
        $select = $query->getSelect();
        $selectField = !$select ? [] : $select->getFields();
        $fields = [];
        if (!empty($selectField)) {
            $bounds = [];
            $hawAggregate = false;
            $hawBound = false;
            foreach ($selectField as $field) {
                $match = [];
                if ($field instanceof AggregateFunctionNode) {
                    $hawAggregate = true;
                    //todo: create aggregate
                } else if (preg_match('/([\w]+)\./', $field, $match)) {
                    $subMatch = [];
                    $name = $match[1];
                    $boundQuery = new Query();
                    if (preg_match('/([\w]+)\.\#([\w]+)?/', $field, $subMatch)) {
                        $withOut = $this->dbTable->table;
                        if (isset($subMatch[2])) {
                            $withOut = $subMatch[2];
                        }
                        $boundQuery->setSelect(new SelectNode(['#' . $withOut]));
                    } else if (preg_match('/[\w]+\.([\w\.\#]+)/', $field, $subMatch)) {
                        $boundQuery->setSelect(new SelectNode([$subMatch[1]]));
                    } else {
                        $boundQuery->setSelect(new SelectNode());
                    }
                    $bounds[] = [$name => $boundQuery];
                } else if (preg_match('/^#([\w]+)?$/', $field, $match)) {
                    $withOut = '';
                    if ($match[1]) {
                        $withOut = $match[1];
                    }
                    foreach ($this->getBoundsTableName() as $bound) {
                        if ($bound != $withOut) {
                            $boundQuery = new Query();
                            $boundQuery->setSelect(new SelectNode());
                            $bounds[] = [$bound => $boundQuery];
                        }
                    }
                } else {
                    $fields[] = $field;
                }
                if ($hawAggregate && $hawBound) {
                    throw new DataStoreException('Cannot use aggregate function with bounds');
                }
            }
            if (!empty($bounds)) {
                $fields['.bounds.'] = $bounds;
            }
        }
        $selectSQL->columns(empty($fields) ? [Select::SQL_STAR] : $fields);
        return $selectSQL;
    }

    public function getBoundsTableName()
    {
        $this->initBound();
        $boundNames = [];
        foreach ($this->boundTables as $bounds) {
            foreach ($bounds as $bound) {
                /** @var Composite $composite */
                $composite = ($bound['table']);
                $boundNames[] = $composite->dbTable->table;
            }
        }
        return $boundNames;
    }

    /**
     * initialize bound table
     */
    protected function initBound()
    {
        if (!isset($this->boundTables) || empty($this->boundTables)) {
            /** @var Adapter $adapter */
            $adapter = $this->dbTable->getAdapter();
            $tableManager = new TableManagerMysql($adapter);
            $metadata = Factory::createSourceFromAdapter($adapter);
            $this->boundTables = [
                'single' => [],
                'multiple' => []
            ];
            /** @var $constraint \Zend\Db\Metadata\Object\ConstraintObject */
            foreach ($metadata->getConstraints($this->dbTable->table) as $constraint) {
                if ($constraint->isForeignKey()) {
                    $this->boundTables['single'][$constraint->getReferencedTableName()] = [
                        'table' => new Composite(new TableGateway($constraint->getReferencedTableName(), $adapter)),
                        'myColumn' => $constraint->getColumns()[0],
                        'column' => $constraint->getReferencedColumns()[0]
                    ];
                }
            }

            foreach ($tableManager->getLinkedTables($this->dbTable->table) as $linkedTable) {
                $this->boundTables['multiple'][$linkedTable['TABLE_NAME']] = [
                    'table' => new Composite(new TableGateway($linkedTable['TABLE_NAME'], $adapter)),
                    'column' => $linkedTable['COLUMN_NAME']
                ];
            }
        }
    }

    public function getSqlQuery(Query $query)
    {
        $conditionBuilder = new SqlConditionBuilder($this->dbTable->getAdapter(), $this->dbTable->getTable());

        $selectSQL = $this->dbTable->getSql()->select();
        $selectSQL->where($conditionBuilder($query->getQuery()));
        $selectSQL = $this->setSelectOrder($selectSQL, $query);
        $selectSQL = $this->setSelectLimitOffset($selectSQL, $query);
        $selectSQL = $this->setSelectColumns($selectSQL, $query);

        $selectSQL = $this->setSelectJoin($selectSQL, $query);

        $fields = $selectSQL->getRawState(Select::COLUMNS);
        if (isset($fields['.bounds.'])) {
            unset($fields['.bounds.']);
            if (empty($fields)) {
                $fields = [Select::SQL_STAR];
            }
            $selectSQL->columns($fields);
        }

        $selectSQL = $this->makeExternalSql($selectSQL);

        //build sql string
        $sql = $this->dbTable->getSql()->buildSqlString($selectSQL);
        //replace double ` char to single.
        $sql = str_replace(["`(", ")`", "``"], ['(', ')', "`"], $sql);
        return $sql;
    }

    public function create($itemData, $rewriteIfExist = false)
    {
        return;
    }

    public function _create($itemData, $rewriteIfExist = false)
    {
        return;
    }

    public function _update($itemData, $createIfAbsent = false)
    {
        return;
    }

    public function update($itemData, $createIfAbsent = false)
    {
        return;
    }

    public function delete($id)
    {
        return;
    }

    public function deleteAll()
    {
        return;
    }
}