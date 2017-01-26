<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace rollun\datastore\Middleware\Factory;

//use Zend\ServiceManager\Factory\FactoryInterface;
//uncomment it ^^ for Zend\ServiceManager V3
use Zend\ServiceManager\Factory\FactoryInterface;
//comment it ^^ for Zend\ServiceManager V3
use Zend\ServiceManager\ServiceLocatorInterface;
use rollun\datastore\RestException;
use Interop\Container\ContainerInterface;
use rollun\datastore\Middleware;
use rollun\datastore\DataStore\DbTable;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use Zend\Stratigility\MiddlewareInterface;

/**
 *
 * @category   rest
 * @package    zaboy
 */
class DataStoreDirectFactory implements FactoryInterface
{

    /**
     * Create and return an instance of the PipeMiddleware for Rest.
     * <br>
     * If StoreMiddleware with same name as name of resource is discribed in config
     * in key 'middleware' - it will use
     * <br>
     * If DataStore with same name as name of resource is discribed in config
     * in key 'dataStore' - it will use for create StoreMiddleware
     * <br>
     * If table in DB with same name as name of resource is exist
     *  - it will use for create TableGateway for create DataStore for create StoreMiddleware
     * <br>
     * Add <br>
     * rollun\datastore\TableGateway\Factory\TableGatewayAbstractFactory <br>
     * rollun\datastore\DataStore\Factory\DbTableAbstractFactory <br>
     * rollun\datastore\Middleware\Factory\DataStoreAbstractFactory <br>
     * to config<br>
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  array $options
     * @return MiddlewareInterface
     * @throws RestException
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

    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return mixed
     * @throws RestException
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        throw new RestException(
            'Don\'t use it as factory in config. ' . PHP_EOL
            . 'Call __invoke directly with resource name as parameter'
        );
    }

}
