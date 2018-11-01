<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\rest\Middleware\Factory;

use rollun\datastore\DataStore\DataStoreException;
use rollun\rest\Middleware\DataStoreRest;
use Interop\Container\ContainerInterface;
use rollun\datastore\AbstractFactoryAbstract;

/**
 * Factory for middleware which contane DataStore
 *
 * config
 * <code>
 *  'middleware' => [
 *      'MiddlewareName' => [
 *          static::KEY_CLASS =>'rollun\rest\MiddlewareType',
 *          'dataStore' => 'rollun\datastore\DataStore\Type'
 *      ],
 *      'MiddlewareAnotherName' => [
 *          static::KEY_CLASS =>'rollun\rest\MiddlewareAnotherType',
 *          'dataStore' => 'rollun\datastore\DataStore\AnotherType'
 *      ],
 *  ...
 *  ],
 * </code>
 * @category   rest
 * @package    zaboy
 */
class DataStoreAbstractFactory extends AbstractFactoryAbstract
{
    const KEY = 'middleware';

    const DEFAULT_MIDDLEWARE__CLASS = DataStoreRest::class;

    /**
     * Can the factory create an instance for the service?
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @return bool
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        $config = $container->get('config');
        return isset($config[static::KEY][$requestedName]);

    }

    /**
     * Create and return an instance of the Middleware.
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return mixed|\rollun\datastore\DataStore\Interfaces\DataStoresInterface
     * @throws DataStoreException
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');
        if(is_string($config[static::KEY][$requestedName])) {
            $requestedClassName = static::DEFAULT_MIDDLEWARE__CLASS;
            if(!$container->has($config[static::KEY][$requestedName])) {
                throw new DataStoreException(
                    'Can\'t get Store ' . $config[static::KEY][$requestedName] . ' for Middleware ' . $requestedName
                );
            }
            $dataStore = $container->get($config[static::KEY][$requestedName]);

        } else {
            $serviceConfig = $config[static::KEY][$requestedName];
            $requestedClassName = $serviceConfig[static::KEY_CLASS];
            //take store for Middleware
            $dataStoreServiceName = isset($serviceConfig['dataStore']) ? $serviceConfig['dataStore'] : null;
            if (!($container->has($dataStoreServiceName))) {
                throw new DataStoreException(
                    'Can\'t get Store ' . $dataStoreServiceName . ' for Middleware ' . $requestedName
                );
            }
            $dataStore = $container->get($dataStoreServiceName);
        }
        return new $requestedClassName($dataStore);
    }

}
