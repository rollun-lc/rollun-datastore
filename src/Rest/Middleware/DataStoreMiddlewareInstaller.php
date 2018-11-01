<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\rest\Middleware;

use rollun\actionrender\Factory\MiddlewarePipeAbstractFactory;
use rollun\datastore\DataStore\DataStorePluginManager;
use rollun\datastore\DataStore\DataStorePluginManagerFactory;
use rollun\rest\Middleware\Factory\DeterminatorFactory;
use rollun\installer\Install\InstallerAbstract;
use Zend\ServiceManager\Factory\InvokableFactory;

class DataStoreMiddlewareInstaller extends InstallerAbstract
{
    /**
     * install
     * @return array
     */
    public function install()
    {
        return [
            'dependencies' => [
                'factories' => [
                    ResourceResolver::class => InvokableFactory::class,
                    RequestDecoder::class => InvokableFactory::class,
                    DataStoreRest::class => DeterminatorFactory::class,
                    DataStorePluginManager::class => DataStorePluginManagerFactory::class,
                ],
            ],
            MiddlewarePipeAbstractFactory::KEY => [
                'api-datastore' => [
                    MiddlewarePipeAbstractFactory::KEY_MIDDLEWARES => [
                        Middleware\ResourceResolver::class,
                        Middleware\RequestDecoder::class,
                        Middleware\DataStoreRest::class,
                        Middleware\JsonRenderer::class,
                    ],
                ],
            ],
            'routes' => [
                [
                    'name' => 'api-datastore',
                    'path' => '/api/datastore[/{resourceName}[/{id}]]',
                    'middleware' => 'api-datastore',
                    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'],
                ],
            ],
        ];
    }

    /**
     * Clean all installation
     * @return void
     */
    public function uninstall()
    {

    }

    /**
     * Return string with description of installable functional.
     * @param string $lang ; set select language for description getted.
     * @return string
     */
    public function getDescription($lang = "en")
    {
        switch ($lang) {
            case "ru":
                $description = "Позволяет обращаться к хранилищу по http.";
                break;
            default:
                $description = "Does not exist.";
        }

        return $description;
    }

    public function isInstall()
    {
        $config = $this->container->get('config');

        return (isset($config['dependencies']['factories'])
            && in_array(
                ImplicitDataStoreMiddlewareAbstractFactory::class,
                $config['dependencies']['abstract_factories']
            )
            && in_array(ResourceResolver::class, $config['dependencies']['factories'])
            && in_array(RequestDecoder::class, $config['dependencies']['factories'])
            && isset($config[LazyLoadMiddlewareAbstractFactory::KEY]['dataStoreLazyLoadPipe'])
            && isset($config[ActionRenderAbstractFactory::KEY]['api-datastore']));
    }

    public function getDependencyInstallers()
    {
        return [
            BasicRenderInstaller::class,
            ActionRenderInstaller::class,
            AttributeParamInstaller::class,
            HeaderSwitchInstaller::class,
        ];
    }
}
