<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 26.01.17
 * Time: 13:16
 */

namespace rollun\datastore\Middleware\Factory;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;

class StoreLazyLoadFactory implements FactoryInterface
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
        $storeMiddlewareLazy = function (
            Request $request,
            Response $response,
            $next = null
        ) use ($container) {
            $resourceName = $request->getAttribute('resourceName');
            $DataStoreDirectFactory = new DataStoreDirectFactory();
            $storeMiddleware = $DataStoreDirectFactory($container, $resourceName);
            return $storeMiddleware($request, $response, $next);
        };

        return $storeMiddlewareLazy;
    }
}
