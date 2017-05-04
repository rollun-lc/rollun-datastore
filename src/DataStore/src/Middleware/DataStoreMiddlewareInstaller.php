<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 13.03.17
 * Time: 11:32
 */

namespace rollun\datastore\Middleware;

use rollun\actionrender\Factory\ActionRenderAbstractFactory;
use rollun\actionrender\Factory\LazyLoadPipeAbstractFactory;
use rollun\actionrender\Factory\MiddlewarePipeAbstractFactory;
use rollun\actionrender\Installers\ActionRenderInstaller;
use rollun\actionrender\Installers\BasicRenderInstaller;
use rollun\actionrender\LazyLoadMiddlewareGetter\Factory\AbstractLazyLoadMiddlewareGetterAbstractFactory;
use rollun\actionrender\LazyLoadMiddlewareGetter\Factory\ResponseRendererAbstractFactory;
use rollun\actionrender\LazyLoadMiddlewareGetter\ResponseRenderer;
use rollun\actionrender\Renderer\Json\JsonRendererAction;
use rollun\datastore\DataStore\Factory\MemoryAbstractFactory;
use rollun\datastore\LazyLoadDSMiddlewareGetter;
use rollun\datastore\Middleware\Factory\HtmlDataStoreRendererFactory;
use rollun\installer\Install\InstallerAbstract;

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
                'invokables' => [
                    ResourceResolver::class => ResourceResolver::class,
                    RequestDecoder::class => RequestDecoder::class,
                    LazyLoadDSMiddlewareGetter::class => LazyLoadDSMiddlewareGetter::class,
                ],
                'factories' => [
                    HtmlDataStoreRendererAction::class => HtmlDataStoreRendererFactory::class
                ],
            ],
            AbstractLazyLoadMiddlewareGetterAbstractFactory::KEY => [
                'dataStoreHtmlJsonRenderer' => [
                    ResponseRendererAbstractFactory::KEY_MIDDLEWARE => [
                        '/application\/json/' => JsonRendererAction::class,
                        '/text\/html/' => 'dataStoreHtmlRenderer'
                    ],
                    ResponseRendererAbstractFactory::KEY_CLASS => ResponseRenderer::class,
                ],
            ],

            LazyLoadPipeAbstractFactory::KEY => [
                'dataStoreLLPipe' => LazyLoadDSMiddlewareGetter::class,
                'dataStoreHtmlJsonRendererLLPipe' => 'dataStoreHtmlJsonRenderer'
            ],

            ActionRenderAbstractFactory::KEY => [
                'api-datastore' => [
                    ActionRenderAbstractFactory::KEY_ACTION_MIDDLEWARE_SERVICE => 'apiDataStoreAction',
                    ActionRenderAbstractFactory::KEY_RENDER_MIDDLEWARE_SERVICE => 'dataStoreHtmlJsonRendererLLPipe'
                ],
            ],

            MiddlewarePipeAbstractFactory::KEY => [
                'apiDataStoreAction' => [
                    MiddlewarePipeAbstractFactory::KEY_MIDDLEWARES => [
                        \rollun\datastore\Middleware\ResourceResolver::class,
                        \rollun\datastore\Middleware\RequestDecoder::class,
                        'dataStoreLLPipe'
                    ]
                ],
                'dataStoreHtmlRenderer' => [
                    MiddlewarePipeAbstractFactory::KEY_MIDDLEWARES => [
                        \rollun\actionrender\Renderer\Html\HtmlParamResolver::class,
                        \rollun\datastore\Middleware\HtmlDataStoreRendererAction::class
                    ]
                ]
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
        return (
            isset($config['dependencies']['invokables']) &&
            isset($config['dependencies']['factories']) &&
            in_array(ResourceResolver::class,$config['dependencies']['invokables']) &&
            in_array( RequestDecoder::class,$config['dependencies']['invokables']) &&
            in_array(LazyLoadDSMiddlewareGetter::class,$config['dependencies']['invokables']) &&
            $config['dependencies']['factories'][HtmlDataStoreRendererAction::class] === HtmlDataStoreRendererFactory::class &&
            isset($config[AbstractLazyLoadMiddlewareGetterAbstractFactory::KEY]['dataStoreHtmlJsonRenderer']) &&
            isset($config[LazyLoadPipeAbstractFactory::KEY]['dataStoreLLPipe']) &&
            isset($config[LazyLoadPipeAbstractFactory::KEY]['dataStoreHtmlJsonRendererLLPipe']) &&
            isset($config[ActionRenderAbstractFactory::KEY]['api-datastore']) &&
            isset($config[MiddlewarePipeAbstractFactory::KEY]['apiDataStoreAction']) &&
            isset($config[MiddlewarePipeAbstractFactory::KEY]['dataStoreHtmlRenderer']) &&
            isset($config[MiddlewarePipeAbstractFactory::KEY]['dataStoreHtmlRenderer'])
        );
    }

    public function getDependencyInstallers()
    {
        return [
            BasicRenderInstaller::class,
            ActionRenderInstaller::class
        ];
    }


}
