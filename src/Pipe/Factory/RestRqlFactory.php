<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace rollun\datastore\Pipe\Factory;

use Zend\ServiceManager\Factory\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use rollun\datastore\RestException;
use Interop\Container\ContainerInterface;
use rollun\datastore\Middleware;
use rollun\datastore\Middleware\Factory\DataStoreDirectFactory;
use rollun\datastore\Pipe\RestRql;
use Zend\Stratigility\MiddlewareInterface;

/**
 *
 * @category   rest
 * @package    zaboy
 */
class RestRqlFactory implements FactoryInterface
{
    /*
     * var $middlewares array
     */

    protected $middlewares;

    /**
     *
     * @param array $addMiddlewares  [10 => 'firstMiddleWare', 350 => afterRqlParser /* object * / ]
     */
    public function __construct($addMiddlewares = [])
    {
        $this->middlewares = $addMiddlewares;
    }

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
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $storeMiddlewareLazy = function (
                $request,
                $response,
                $next = null
                ) use ($container) {
            $resourceName = $request->getAttribute('Resource-Name');
            $DataStoreDirectFactory = new DataStoreDirectFactory();
            $storeMiddleware = $DataStoreDirectFactory($container, $resourceName);
            return $storeMiddleware($request, $response, $next);
        };

        $this->middlewares[100] = new Middleware\ResourceResolver();
        $this->middlewares[200] = new Middleware\RequestDecoder();
        $this->middlewares[300] = $storeMiddlewareLazy;
        $this->middlewares[400] = new Middleware\ResponseEncoder();
        $this->middlewares[500] = new Middleware\ResponseReturner();
        //$middlewares[600] = new Middleware\$errorHandler();

        ksort($this->middlewares);
        return new RestRql($this->middlewares);
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

    public function getMiddlewares()
    {
        return $this->middlewares;
    }

}
