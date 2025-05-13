<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore;

use Psr\Container\ContainerInterface;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

/**
 * Create and return an instance of the DataStore which based on DbTable
 *
 * This Factory depends on Container (which should return an 'config' as array)
 *
 * The configuration MUST contain:
 * <code>
 * 'DataStore' => [
 *     'TheStore' => [
 *         'class' => 'rollun\datastore\DataStore\ClassName',
 *     ]
 * ]
 * </code>
 *
 * @uses zend-db
 * @see https://github.com/zendframework/zend-db
 * @category   rest
 * @package    zaboy
 */
abstract class AbstractFactoryAbstract implements AbstractFactoryInterface
{
    public const KEY_CLASS = 'class';

    /**
     * Can the factory create an instance for the service?
     *
     * For Service manager V3
     * Edit 'use' section if need:
     * Change:
     * 'use Laminas\ServiceManager\AbstractFactoryInterface;' for V2 to
     * 'use Laminas\ServiceManager\Factory\AbstractFactoryInterface;' for V3
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @return bool
     */
    abstract public function canCreate(ContainerInterface $container, $requestedName);

    /**
     * Create and return an instance of the DataStore.
     *
     * 'use Laminas\ServiceManager\AbstractFactoryInterface;' for V2 to
     * 'use Laminas\ServiceManager\Factory\AbstractFactoryInterface;' for V3
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  array $options
     * @return DataStoresInterface
     */
    abstract public function __invoke(ContainerInterface $container, $requestedName, array $options = null);

    /**
     * Determine if we can create a service with name
     *
     * For Service manager V2
     * Edit 'use' section if need:
     * Change:
     * 'use Laminas\ServiceManager\Factory\AbstractFactoryInterface;' for V3 to
     * 'use Laminas\ServiceManager\AbstractFactoryInterface;' for V2
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param $name
     * @param $requestedName
     * @return bool
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        return $this->canCreate($serviceLocator, $requestedName);
    }

    /**
     * Create service with name
     *
     * For Service manager V2
     * Edit 'use' section if need:
     * Change:
     * 'use Laminas\ServiceManager\Factory\AbstractFactoryInterface;' for V3 to
     * 'use Laminas\ServiceManager\AbstractFactoryInterface;' for V2
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param $name
     * @param $requestedName
     * @return mixed
     */
    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        return $this->__invoke($serviceLocator, $requestedName);
    }
}
