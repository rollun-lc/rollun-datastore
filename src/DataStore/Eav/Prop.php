<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace rollun\datastore\DataStore\Eav;

use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\DbTable;
use rollun\datastore\TableGateway\TableManagerMysql;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Metadata\Source\Factory;

/**
 *
 * Add to config:
 * <code>
 *     'dataStore' => [
 *         'SomeResourceName' => [
 *             'class' => Prop::class,
 *             'tableName' => 'table_some_resource_name'
 *         ],
 *     ],
 * </code>
 *
 * Tablet 'able_some_resource_name' must be exist. Add code to  Eav\installer for create it.
 *
 */
class Prop extends DbTable
{

    public function createWithEntity($propData, $entityId, $entityName, $propColumn)
    {
        $linkedColumn = $this->getLinkedColumn($entityName, $propColumn);
        if (is_null($linkedColumn)) {
            throw new DataStoreException('Wrong linked column: ' . $propColumn);
        }
        $this->dbTable->delete([$linkedColumn => $entityId]);
        $result = [];
        foreach ($propData as $row) {
            $row[$linkedColumn] = $entityId;
            $result[] = $this->_create($row);
        }
        return $result;
    }

    public function updateWithEntity($propData, $entityId, $entityName, $propColumn)
    {
        $linkedColumn = $this->getLinkedColumn($entityName, $propColumn);
        if (is_null($linkedColumn)) {
            throw new DataStoreException('Wrong linked column: ' . $propColumn);
        }
        $result = [];
        foreach ($propData as $row) {
            if (!isset($row[$this->getIdentifier()]) ||
                $this->read($row[$this->getIdentifier()]) == null
            ) {
                $row[$linkedColumn] = $entityId;
                $result[] = $this->_create($row);
            } else if (!empty(array_diff_assoc($row, $this->read($row[$this->getIdentifier()])))) {
                $result[] = $this->_update($row);
            }
        }
    }

    public function getPropName()
    {
        $tableName = $this->dbTable->table;
        return SysEntities::getPropName($tableName);
    }

    public function getPropTableName()
    {
        return $tableName = $this->dbTable->table;
    }


    public function getLinkedColumn($entityName, $propColumn)
    {
        /** @var Adapter $adapter */
        $adapter = $this->dbTable->getAdapter();
        $mysqlManager = new TableManagerMysql($adapter);
        $columnsNames = $mysqlManager->getColumnsNames($this->dbTable->table);

        //'prop_name.column_name' or 'prop_name'
        $linkedColumn = strpos($propColumn, '.') ?
            //prop_name.column_name
            (isset(explode('.', $propColumn)[1]) && in_array(explode('.', $propColumn)[1], $columnsNames) ?
                //column_name
                explode('.', $propColumn)[1] :
                //error
                null
            ) :
            //prop_name
            (in_array($entityName . SysEntities::ID_SUFFIX, $columnsNames) ?
                //entity_id
                $entityName . SysEntities::ID_SUFFIX :
                (in_array(SysEntities::TABLE_NAME . SysEntities::ID_SUFFIX, $columnsNames) ?
                    //sys_entities_id
                    SysEntities::TABLE_NAME . SysEntities::ID_SUFFIX :
                    //error
                    null
                )
            );
        return $linkedColumn;
    }


}
