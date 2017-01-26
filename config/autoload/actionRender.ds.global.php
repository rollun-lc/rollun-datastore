<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 24.01.17
 * Time: 17:32
 */

use rollun\actionrender\Factory\ActionRenderAbstractFactory;
use rollun\actionrender\Factory\MiddlewarePipeAbstractFactory;
use rollun\actionrender\Renderer\ResponseRendererAbstractFactory;
use rollun\datastore\Middleware\RequestDecoder;
use rollun\datastore\Middleware\ResourceResolver;

return [
    'dependencies' => [
        'invokables' => [
            \rollun\datastore\Middleware\ResourceResolver::class =>
                \rollun\datastore\Middleware\ResourceResolver::class,
            \rollun\datastore\Middleware\RequestDecoder::class => \rollun\datastore\Middleware\RequestDecoder::class,
        ],
        'factories' => [
            'dataStoreMiddleware' => \rollun\datastore\Middleware\Factory\StoreLazyLoadFactory::class,
            \rollun\datastore\Middleware\HtmlDataStoreRendererAction::class =>
                \rollun\actionrender\Renderer\Html\HtmlRendererFactory::class
        ]
    ],
    ResponseRendererAbstractFactory::KEY_RESPONSE_RENDERER => [
        'dataStoreHtmlJsonRenderer' => [
            ResponseRendererAbstractFactory::KEY_ACCEPT_TYPE_PATTERN => [
                //pattern => middleware-Service-Name
                '/application\/json/' => \rollun\actionrender\Renderer\Json\JsonRendererAction::class,
                '/text\/html/' => 'dataStoreHtmlRenderer'
            ]
        ]
    ],
    ActionRenderAbstractFactory::KEY_AR_SERVICE => [
        'api-rest' => [
            ActionRenderAbstractFactory::KEY_AR_MIDDLEWARE => [
                ActionRenderAbstractFactory::KEY_ACTION_MIDDLEWARE_SERVICE => 'apiRestAction',
                ActionRenderAbstractFactory::KEY_RENDER_MIDDLEWARE_SERVICE => 'dataStoreHtmlJsonRenderer'
            ]
        ]
    ],
    MiddlewarePipeAbstractFactory::KEY_AMP => [
        'apiRestAction' => [
            'middlewares' => [
                \rollun\datastore\Middleware\ResourceResolver::class,
                \rollun\datastore\Middleware\RequestDecoder::class,
                'dataStoreMiddleware'
            ]
        ],
        'dataStoreHtmlRenderer' => [
            'middlewares' => [
                \rollun\actionrender\Renderer\Html\HtmlParamResolver::class,
                \rollun\datastore\Middleware\HtmlDataStoreRendererAction::class
            ]
        ]
    ],
];
