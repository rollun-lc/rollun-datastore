<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 18.01.17
 * Time: 17:48
 */

namespace rollun\datastore\Viewer\Pipe\Factory;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use rollun\datastore\Middleware\RequestDecoder;
use rollun\datastore\Middleware\ResourceResolver;
use rollun\datastore\Viewer\Factory\ViewerDirectFactory;
use rollun\datastore\Viewer\ViewerPipe;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;

class ViewerPipeFactory implements FactoryInterface
{
    protected $middlewares;
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
        $config = $container->get('config');

        $serviceMiddleware = function (
            $request,
            $response,
            $next = null
        ) use ($container) {
            $resourceName = $request->getAttribute('Resource-Name');
            $viewResourceName = new ViewerDirectFactory();
            $viewer = $viewResourceName($container, $resourceName);
            return $viewer($request, $response, $next);
        };

        $this->middlewares[100] = new ResourceResolver();
        $this->middlewares[200] = new RequestDecoder();
        $this->middlewares[300] = $serviceMiddleware();

        if (isset($config['ViewerPipe']['middlewares'])) {
            $middlewares = $config['ViewerPipe']['middlewares'];
            $this->middlewares = array_merge($this->middlewares, $middlewares);
        }
        ksort($this->middlewares);
        return new ViewerPipe($this->middlewares);
    }
}
