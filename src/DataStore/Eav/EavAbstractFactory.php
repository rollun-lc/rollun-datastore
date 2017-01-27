<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace rollun\datastore\DataStore\Eav;

use Interop\Container\ContainerInterface;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\Factory\DataStoreAbstractFactory;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\TableGateway\TableGateway;

/**
 * Create and return an instance of the DataStore which based on DbTable
 *
 * This Factory depends on Container (which should return an 'config' as array)
 *
 * The configuration can contain:
 * <code>
 *    'db' => [
 *        'driver' => 'Pdo_Mysql',
 *        'host' => 'localhost',
 *        'database' => '',
 *    ]
 * 'DataStore' => [
 *
 *     'DbTable' => [
 *         'class' => 'mydatabase',
 *         'tableName' => 'mytableName',
 *         'dbAdapter' => 'db' // Service Name. 'db' by default
 *     ]
 * ]
 * </code>
 *
 * @uses zend-db
 * @see https://github.com/zendframework/zend-db
 * @category   rest
 * @package    zaboy
 */
class EavAbstractFactory extends DataStoreAbstractFactory
{

    const DB_SERVICE_NAME = 'eav db';
    const DB_NAME_DELIMITER = '~';

    public function canCreate(ContainerInterface $container, $requestedName)
    {
        //'SuperEtity - 'entity_table_name_1-entity_table_name_1'
        $superEtity = strpos($requestedName, SuperEntity::INNER_JOIN);
        if ($superEtity) {
            $eavDataStores = explode(SuperEntity::INNER_JOIN, $requestedName);
            foreach ($eavDataStores as $eavDataStore) {
                //db.entity_table_name_1 -> entity_table_name_1
                $locate = explode(EavAbstractFactory::DB_NAME_DELIMITER, $eavDataStore);
                $eavDataStore = count($locate) == 1 ? $locate[0] : $locate[1];
                if (strpos($eavDataStore, SysEntities::ENTITY_PREFIX) !== 0) {
                    return false;
                }
            }
            return true;
        } else {
            //db.entity_table_name_1 -> entity_table_name_1
            $locate = explode(EavAbstractFactory::DB_NAME_DELIMITER, $requestedName);
            $eavDataStore = count($locate) == 1 ? $locate[0] : $locate[1];
            return strpos($eavDataStore, SysEntities::ENTITY_PREFIX) === 0 ||
                strpos($eavDataStore, SysEntities::PROP_PREFIX) === 0 ||
                $eavDataStore == SysEntities::TABLE_NAME;
        }
    }

    /**
     * Create and return an instance of the DataStore.
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  array $options
     * @return DataStoresInterface
     * @throws DataStoreException
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $dbAdapterName = $this->getDbAdapterName($requestedName);
        $db = $container->has($dbAdapterName) ? $container->get($dbAdapterName) : null;
        if (null === $db) {
            throw new DataStoreException(
                'Can\'t create Zend\Db\TableGateway\TableGateway for ' . $requestedName
            );
        }

        //'SuperEtity - 'entity_table_name_1-entity_table_name_1'
        if (strpos($requestedName, SuperEntity::INNER_JOIN)) {
            $eavDataStores = $this->getEavDataStores($requestedName);
            $eavDataStoresObjests = [];
            foreach ($eavDataStores as $eavDataStore) {
                $eavDataStoresObjests[] = $this->getEavDataStore($db, $eavDataStore);
                $eavDataStoresObjests[] = SuperEntity::INNER_JOIN;
            }
            array_pop($eavDataStoresObjests);
            $tableGateway = new TableGateway(SysEntities::TABLE_NAME, $db);
            $result = new SuperEntity($tableGateway, $eavDataStoresObjests);
            return $result;
        }
        //'sys_entities' or 'entity_table_name' or 'prop_table_name'
        $requestedName = $this->getEavDataStores($requestedName)[0];
        return $this->getEavDataStore($db, $requestedName);
    }

    public function getEavDataStore(AdapterInterface $db, $requestedName)
    {
        //$requestedName = 'sys_entities' or 'entity_table_name' or 'prop_table_name'
        $tableGateway = new TableGateway($requestedName, $db);
        //'sys_entities' or 'entity_table_name' or 'prop_table_name'
        switch (explode('_', $requestedName)[0] . '_') {
            case SysEntities::ENTITY_PREFIX :
                return new Entity($tableGateway);
            case SysEntities::PROP_PREFIX :
                return new Prop($tableGateway);
            case explode('_', SysEntities::TABLE_NAME)[0] . '_':
                return new SysEntities($tableGateway);
            default:
                throw new DataStoreException(
                    'Can\'t create service for ' . $requestedName
                );
        }
    }

    /**
     * @param $requestedName
     * @return string
     */
    protected function getDbAdapterName($requestedName)
    {
        if (strpos($requestedName, SuperEntity::INNER_JOIN)) {
            $eavDataStores = explode(SuperEntity::INNER_JOIN, $requestedName);
            //db.entity_table_name_1 -> entity_table_name_1
            $locate = explode(EavAbstractFactory::DB_NAME_DELIMITER, $eavDataStores[0]);
        } else {
            $locate = explode(EavAbstractFactory::DB_NAME_DELIMITER, $requestedName);
        }
        return count($locate) == 2 ? $locate[0] : static::DB_SERVICE_NAME;
    }

    protected function getEavDataStores($requestedName)
    {
        if (strpos($requestedName, SuperEntity::INNER_JOIN)) {
            $eavDataStores = explode(SuperEntity::INNER_JOIN, $requestedName);
            foreach ($eavDataStores as &$eavDataStore) {
                //db.entity_table_name_1 -> entity_table_name_1
                $locate = explode(EavAbstractFactory::DB_NAME_DELIMITER, $eavDataStore);
                $eavDataStore = count($locate) == 1 ? $locate[0] : $locate[1];
            }
            return $eavDataStores;
        } else {
            //db.entity_table_name_1 -> entity_table_name_1
            $locate = explode(EavAbstractFactory::DB_NAME_DELIMITER, $requestedName);
            $eavDataStore = count($locate) == 1 ? $locate[0] : $locate[1];
            return [$eavDataStore];
        }
    }
}
