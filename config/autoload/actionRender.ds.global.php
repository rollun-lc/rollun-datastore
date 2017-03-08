<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 24.01.17
 * Time: 17:32
 */

use rollun\actionrender\Factory\ActionRenderAbstractFactory;
use rollun\actionrender\Factory\LazyLoadPipeAbstractFactory;
use rollun\actionrender\Factory\MiddlewarePipeAbstractFactory;
use rollun\actionrender\LazyLoadMiddlewareGetter\Factory\AbstractLazyLoadMiddlewareGetterAbstractFactory;
use rollun\actionrender\LazyLoadMiddlewareGetter\Factory\AttributeAbstractFactory;
use rollun\actionrender\LazyLoadMiddlewareGetter\Factory\AttributeSwitchAbstractFactory;
use rollun\actionrender\LazyLoadMiddlewareGetter\Factory\ResponseRendererAbstractFactory;
use rollun\datastore\LazyLoadDSMiddlewareGetter;

return [
    'dependencies' => [
        'invokables' => [
            \rollun\datastore\Middleware\ResourceResolver::class =>
                \rollun\datastore\Middleware\ResourceResolver::class,
            \rollun\datastore\Middleware\RequestDecoder::class => \rollun\datastore\Middleware\RequestDecoder::class,
            LazyLoadDSMiddlewareGetter::class => LazyLoadDSMiddlewareGetter::class,
        ],
        'factories' => [
            \rollun\datastore\Middleware\HtmlDataStoreRendererAction::class =>
                \rollun\datastore\Middleware\Factory\HtmlDataStoreRendererFactory::class
        ],
        'abstract_factories' => [
            MiddlewarePipeAbstractFactory::class,
            ActionRenderAbstractFactory::class,
            AttributeAbstractFactory::class,
            ResponseRendererAbstractFactory::class,
            LazyLoadPipeAbstractFactory::class,
            AttributeSwitchAbstractFactory::class,
        ]
    ],

    AbstractLazyLoadMiddlewareGetterAbstractFactory::KEY => [
        /*'dataStoreMiddlewareByResourceName' => [
            AttributeAbstractFactory::KEY_ATTRIBUTE_NAME => 'resourceName',
            AttributeAbstractFactory::KEY_CLASS => LazyLoadDSMiddlewareGetter::class,
        ],*/
        'dataStoreHtmlJsonRenderer' => [
            ResponseRendererAbstractFactory::KEY_MIDDLEWARE => [
                '/application\/json/' => \rollun\actionrender\Renderer\Json\JsonRendererAction::class,
                '/text\/html/' => 'dataStoreHtmlRenderer'
            ],
            ResponseRendererAbstractFactory::KEY_CLASS => \rollun\actionrender\LazyLoadMiddlewareGetter\ResponseRenderer::class,
        ],
    ],

    LazyLoadPipeAbstractFactory::KEY => [
        'dataStoreLLPipe' => LazyLoadDSMiddlewareGetter::class,
        'dataStoreHtmlJsonRendererLLPipe' => 'dataStoreHtmlJsonRenderer'
    ],

    ActionRenderAbstractFactory::KEY => [
        'api-rest' => [
            ActionRenderAbstractFactory::KEY_ACTION_MIDDLEWARE_SERVICE => 'apiRestAction',
            ActionRenderAbstractFactory::KEY_RENDER_MIDDLEWARE_SERVICE => 'dataStoreHtmlJsonRendererLLPipe'
        ],
    ],

    MiddlewarePipeAbstractFactory::KEY => [
        'apiRestAction' => [
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
];
