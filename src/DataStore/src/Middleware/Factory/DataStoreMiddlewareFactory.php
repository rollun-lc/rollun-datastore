<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 08.03.17
 * Time: 13:51
 */

namespace rollun\datastore\Middleware\Factory;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\DbTable;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use rollun\datastore\RestException;
use rollun\datastore\Middleware;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use Zend\Stratigility\MiddlewareInterface;

class DataStoreMiddlewareFactory implements FactoryInterface
{

    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $resourceName = $requestedName;
        if (!$container->has($resourceName)) {
            throw new RestException(
                'Can\'t make Middleware\DataStoreRest for resource: ' . $resourceName
            );
        }
        $resourceObject = $container->get($resourceName);
        switch (true) {
            case is_a($resourceObject, 'Zend\Db\TableGateway\TableGateway'):
                $tableGateway = $resourceObject;
                $resourceObject = new DbTable($tableGateway);
            case ($resourceObject instanceof DataStoresInterface):
                $dataStore = $resourceObject;
                $resourceObject = new Middleware\DataStoreRest($dataStore);
            case $resourceObject instanceof MiddlewareInterface:
                $storeMiddleware = $resourceObject;
            default:
                if (!isset($storeMiddleware)) {
                    throw new RestException(
                        'Can\'t make Middleware\DataStoreRest'
                        . ' for resource: ' . $resourceName
                    );
                }
        }
        return $storeMiddleware;

    }
}
