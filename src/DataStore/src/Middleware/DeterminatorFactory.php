<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Middleware;

use Interop\Container\ContainerInterface;
use rollun\datastore\DataStore\DataStorePluginManager;
use Zend\ServiceManager\Factory\FactoryInterface;

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
