<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace rollun\datastore\DataStore\Factory;

use Interop\Container\ContainerInterface;
use rollun\datastore\DataStore\Cacheable;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\Memory;

class CacheableAbstractFactory extends DataStoreAbstractFactory
{

    const KEY_DATASOURCE = 'dataSource';
    const KEY_CACHEABLE = 'cacheable';
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
     * @return mixed
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
                'There is DataSource not created ' . $requestedName . 'in config \'dataStore\''
                );
            }
        } else {
            $this::$KEY_IN_CREATE = 0;
            throw new DataStoreException(
            'There is DataSource for ' . $requestedName . 'in config \'dataStore\''
            );
        }

        if (isset($serviceConfig[self::KEY_CACHEABLE])) {
            if ($container->has($serviceConfig[self::KEY_CACHEABLE])) {
                $cashStore = $container->get($serviceConfig[self::KEY_CACHEABLE]);
            } else {
                $this::$KEY_IN_CREATE = 0;
                throw new DataStoreException(
                'There is DataSource for ' . $serviceConfig[self::KEY_CACHEABLE] . 'in config \'dataStore\''
                );
            }
        } else {
            $cashStore = new Memory();
        }

        $this::$KEY_IN_CREATE = 0;

        //$cashStore = isset($serviceConfig['cashStore']) ?  new $serviceConfig['cashStore']() : null;
        return new $requestedClassName($getAll, $cashStore);
    }

}
