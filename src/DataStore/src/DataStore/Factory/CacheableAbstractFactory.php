<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Factory;

use Psr\Container\ContainerInterface;
use rollun\datastore\DataStore\Cacheable;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\Memory;

/**
 * Create and return an instance of the array in Cacheable
 * This Factory depends on Container (which should return an 'config' as array)
 *
 * The configuration can contain:
 * <code>
 * 'dataStore' => [
 *      'testCacheable' => [
 *          'class' => \rollun\datastore\DataStore\Cacheable::class,
 *          'dataSource' => 'testDataSourceDb',
 *          'cacheable' => 'testDbTable'
 *      ]
 * ]
 * </code>
 *
 * Class CacheableAbstractFactory
 * @package rollun\datastore\DataStore\Factory
 */
class CacheableAbstractFactory extends DataStoreAbstractFactory
{
    public const KEY_DATASOURCE = 'dataSource';

    public const KEY_CACHEABLE = 'cacheable';

    public const KEY_IS_REFRESH = 'isRefresh';

    public static $KEY_DATASTORE_CLASS = Cacheable::class;

    protected static $KEY_IN_CREATE = 0;

    public function canCreate(ContainerInterface $container, $requestedName)
    {
        $config = $container->get('config');

        if (!isset($config[self::KEY_DATASTORE][$requestedName][self::KEY_CLASS])) {
            $result = false;
        } else {
            $requestedClassName = $config[self::KEY_DATASTORE][$requestedName][self::KEY_CLASS];
            $result = is_a($requestedClassName, $this::$KEY_DATASTORE_CLASS, true);
        }

        return $result;
    }

    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return Cacheable|\rollun\datastore\DataStore\Interfaces\DataStoresInterface
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

        if (isset($serviceConfig[self::KEY_DATASOURCE])) {
            if ($container->has($serviceConfig[self::KEY_DATASOURCE])) {
                $getAll = $container->get($serviceConfig[self::KEY_DATASOURCE]);
            } else {
                $this::$KEY_IN_CREATE = 0;

                throw new DataStoreException(
                    "There is DataSource not created {$requestedName} in config 'dataStore'"
                );
            }
        } else {
            $this::$KEY_IN_CREATE = 0;

            throw new DataStoreException("There is DataSource for {$requestedName} in config 'dataStore'");
        }

        if (isset($serviceConfig[self::KEY_CACHEABLE])) {
            if ($container->has($serviceConfig[self::KEY_CACHEABLE])) {
                $cashStore = $container->get($serviceConfig[self::KEY_CACHEABLE]);
            } else {
                $this::$KEY_IN_CREATE = 0;

                throw new DataStoreException(
                    "There is DataSource for {$serviceConfig[self::KEY_CACHEABLE]} in config 'dataStore'"
                );
            }
        } else {
            $cashStore = new Memory();
        }

        $this::$KEY_IN_CREATE = 0;

        /** @var Cacheable $cashable */
        $cashable = new $requestedClassName($getAll, $cashStore);

        if (isset($serviceConfig[self::KEY_IS_REFRESH]) && $serviceConfig[self::KEY_IS_REFRESH]) {
            $cashable->refresh();
        }

        return $cashable;
    }
}
