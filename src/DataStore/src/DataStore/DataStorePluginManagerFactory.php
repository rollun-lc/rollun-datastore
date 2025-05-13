<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

/**
 * Concrete DataStorePluginManager factory
 *
 * Class DataStorePluginManagerFactory
 * @package rollun\datastore\DataStore
 */
class DataStorePluginManagerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return DataStorePluginManager
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $middlewarePluginManager = new DataStorePluginManager($container);
        $config = $container->get("config");
        $middlewarePluginManager->configure($config["dependencies"]);

        return $middlewarePluginManager;
    }
}
