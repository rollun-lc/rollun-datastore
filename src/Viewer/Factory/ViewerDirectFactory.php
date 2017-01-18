<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 18.01.17
 * Time: 18:20
 */

namespace rollun\datastore\Viewer\Factory;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use rollun\datastore\Viewer\ViewerInterface;
use rollun\datastore\Viewer\ViewerAction;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;

class ViewerDirectFactory implements FactoryInterface
{
    const SERVICE_PREFIX = 'view';

    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return ViewerAction
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $resourceName = $requestedName;
        if (!$container->has($resourceName)) {
            throw new ServiceNotFoundException(
                'Can\'t make service with name: ' . $resourceName
            );
        }
        $viewResourceName  = static::SERVICE_PREFIX . $resourceName;
        if (!$container->has($viewResourceName)) {
            throw new ServiceNotFoundException(
                'Can\'t make Viewer for resource: ' . $viewResourceName
            );
        }
        /** @var ViewerInterface $viewerService */
        $viewerService = $container->get($viewResourceName);
        $viewerAction = new ViewerAction($viewerService);
        return $viewerAction;
    }
}
