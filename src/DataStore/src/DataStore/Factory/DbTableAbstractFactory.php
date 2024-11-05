<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Factory;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\DbTable;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;

/**
 * Create and return an instance of the DataStore which based on DbTable
 * This Factory depends on Container (which should return an 'config' as array)
 *
 * The configuration can contain:
 * <code>
 *  'db' => [
 *      'driver' => 'Pdo_Mysql',
 *      'host' => 'localhost',
 *      'database' => '',
 *  ],
 *  'dataStore' => [
 *      'DbTable' => [
 *          'class' => \rollun\datastore\DataStore\DbTable::class,
 *          'tableName' => 'myTableName',
 *          'dbAdapter' => 'db' // service name, optional
 *          'sqlQueryBuilder' => 'sqlQueryBuilder' // service name, optional
 *      ]
 *  ]
 * </code>
 *
 * Class DbTableAbstractFactory
 * @package rollun\datastore\DataStore\Factory
 */
class DbTableAbstractFactory extends DataStoreAbstractFactory
{
    public const KEY_TABLE_NAME = 'tableName';
    public const KEY_TABLE_GATEWAY = 'tableGateway';
    public const KEY_DB_ADAPTER = 'dbAdapter';

    public static $KEY_DATASTORE_CLASS = DbTable::class;

    protected static $KEY_IN_CREATE = 0;

    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return DbTable
     * @throws DataStoreException
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        if ($this::$KEY_IN_CREATE) {
            throw new DataStoreException("Create will be called without pre call canCreate method");
        }

        $this::$KEY_IN_CREATE = 1;

        $config = $container->get('config');
        $serviceConfig = $config[self::KEY_DATASTORE][$requestedName];
        $requestedClassName = $serviceConfig[self::KEY_CLASS];
        $tableGateway = $this->getTableGateway($container, $serviceConfig, $requestedName);
        $writeLogs = $serviceConfig[DataStoreAbstractFactory::KEY_WRITE_LOGS] ?? false;

        if (!is_bool($writeLogs)) {
            throw new ServiceNotCreatedException(
                "$requestedName datastore config error: " . self::KEY_WRITE_LOGS . ' should be bool value.'
            );
        }

        $this::$KEY_IN_CREATE = 0;

        return new $requestedClassName($tableGateway, $writeLogs, $container->get(LoggerInterface::class));
    }

    /**
     * @param ContainerInterface $container
     * @param $serviceConfig
     * @param $requestedName
     * @return TableGateway
     * @throws DataStoreException
     */
    protected function getTableGateway(ContainerInterface $container, $serviceConfig, $requestedName)
    {
        if (isset($serviceConfig[self::KEY_TABLE_GATEWAY])) {
            if ($container->has($serviceConfig[self::KEY_TABLE_GATEWAY])) {
                $tableGateway = $container->get($serviceConfig[self::KEY_TABLE_GATEWAY]);
            } else {
                $this::$KEY_IN_CREATE = 0;

                throw new DataStoreException(
                    'Can\'t create ' . $serviceConfig[self::KEY_TABLE_GATEWAY]
                );
            }
        } elseif (isset($serviceConfig[self::KEY_TABLE_NAME])) {
            $tableName = $serviceConfig[self::KEY_TABLE_NAME];

            $dbServiceName = isset($serviceConfig[self::KEY_DB_ADAPTER]) ? $serviceConfig[self::KEY_DB_ADAPTER] : 'db';
            $db = $container->has($dbServiceName) ? $container->get($dbServiceName) : null;

            if (null !== $db) {
                $tableGateway = new TableGateway($tableName, $db);
            } else {
                $this::$KEY_IN_CREATE = 0;

                throw new DataStoreException(
                    'Can\'t create Laminas\Db\TableGateway\TableGateway for ' . $tableName
                );
            }
        } else {
            $this::$KEY_IN_CREATE = 0;

            throw new DataStoreException(
                'There is not table name for ' . $requestedName . 'in config \'dataStore\''
            );
        }

        return $tableGateway;
    }
}
