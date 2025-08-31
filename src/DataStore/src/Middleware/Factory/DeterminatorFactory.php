<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Middleware\Factory;

use Psr\Container\ContainerInterface;
use rollun\datastore\DataStore\DataStorePluginManager;
use rollun\datastore\Middleware\Determinator;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * Create Determinator middleware
 *
 * Class DeterminatorFactory
 * @package rollun\datastore\Middleware
 */
class DeterminatorFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return object|Determinator
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $dataStorePluginManager = $container->get(DataStorePluginManager::class);

        return new Determinator($dataStorePluginManager);
    }
}
