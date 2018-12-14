<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Middleware\Factory;

use Interop\Container\ContainerInterface;
use rollun\datastore\Middleware\DataStoreApi;
use rollun\datastore\Middleware\Determinator;
use Zend\ServiceManager\Factory\FactoryInterface;

/**
 * Create DataStoreApi middleware
 *
 * Class DataStoreApiFactory
 * @package rollun\datastore\Middleware\Factory
 */
class DataStoreApiFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return object|DataStoreApi
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $dataStoreDeterminator = $container->get(Determinator::class);

        return new DataStoreApi($dataStoreDeterminator);
    }
}
