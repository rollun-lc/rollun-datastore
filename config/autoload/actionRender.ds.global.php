<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 24.01.17
 * Time: 17:32
 */

use rollun\actionrender\Factory\ActionRenderAbstractFactory;
use rollun\actionrender\Factory\LazyLoadDirectAbstractFactory;
use rollun\actionrender\Factory\LazyLoadResponseRendererAbstractFactory;
use rollun\actionrender\Factory\MiddlewarePipeAbstractFactory;

return [
    'dependencies' => [
        'invokables' => [
            \rollun\datastore\Middleware\ResourceResolver::class =>
                \rollun\datastore\Middleware\ResourceResolver::class,
            \rollun\datastore\Middleware\RequestDecoder::class => \rollun\datastore\Middleware\RequestDecoder::class,
        ],
        'factories' => [
            \rollun\datastore\Middleware\HtmlDataStoreRendererAction::class =>
                \rollun\datastore\Middleware\Factory\HtmlDataStoreRendererFactory::class
        ],
        'abstract_factories' => [
            \rollun\actionrender\Factory\LazyLoadDirectAbstractFactory::class,
            MiddlewarePipeAbstractFactory::class,
            ActionRenderAbstractFactory::class,
            LazyLoadResponseRendererAbstractFactory::class,
        ]
    ],

    LazyLoadResponseRendererAbstractFactory::KEY => [
        'dataStoreHtmlJsonRenderer' => [
            LazyLoadResponseRendererAbstractFactory::KEY_ACCEPT_TYPE_PATTERN => [
                //pattern => middleware-Service-Name
                '/application\/json/' => \rollun\actionrender\Renderer\Json\JsonRendererAction::class,
                '/text\/html/' => 'dataStoreHtmlRenderer'
            ]
        ]
    ],

    LazyLoadDirectAbstractFactory::KEY => [
        'dataStoreMiddleware' => [
            LazyLoadDirectAbstractFactory::KEY_DIRECT_FACTORY =>
                \rollun\datastore\Middleware\Factory\DataStoreDirectFactory::class
        ]
    ],

    ActionRenderAbstractFactory::KEY => [
        'api-rest' => [
            ActionRenderAbstractFactory::KEY_ACTION_MIDDLEWARE_SERVICE => 'apiRestAction',
            ActionRenderAbstractFactory::KEY_RENDER_MIDDLEWARE_SERVICE => 'dataStoreHtmlJsonRenderer'
        ]
    ],

    MiddlewarePipeAbstractFactory::KEY => [
        'apiRestAction' => [
            MiddlewarePipeAbstractFactory::KEY_MIDDLEWARES => [
                \rollun\datastore\Middleware\ResourceResolver::class,
                \rollun\datastore\Middleware\RequestDecoder::class,
                'dataStoreMiddleware'
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
