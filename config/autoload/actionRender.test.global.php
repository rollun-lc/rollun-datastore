<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 24.01.17
 * Time: 17:32
 */

use rollun\actionrender\Factory\ActionRenderAbstractFactory;
use rollun\actionrender\Factory\MiddlewarePipeAbstractFactory;
use rollun\datastore\Middleware\RequestDecoder;
use rollun\datastore\Middleware\ResourceResolver;

return [
    'dependencies' => [
        'invokables' => [
            \rollun\actionrender\Example\Api\HelloAction::class => \rollun\actionrender\Example\Api\HelloAction::class,
            \rollun\datastore\Middleware\ResourceResolver::class =>
                \rollun\datastore\Middleware\ResourceResolver::class,
            \rollun\datastore\Middleware\RequestDecoder::class => \rollun\datastore\Middleware\RequestDecoder::class,
        ],
        'factories' => [
            'dataStoreMiddleware' => \rollun\datastore\Middleware\Factory\StoreLazyLoadFactory::class
        ]
    ],
    ActionRenderAbstractFactory::KEY_AR_SERVICE => [
        'home-service' => [
            ActionRenderAbstractFactory::KEY_AR_MIDDLEWARE => [
                ActionRenderAbstractFactory::KEY_ACTION_MIDDLEWARE_SERVICE =>
                    \rollun\actionrender\Example\Api\HelloAction::class,
                ActionRenderAbstractFactory::KEY_RENDER_MIDDLEWARE_SERVICE => 'simpleHtmlJsonRenderer'
            ]
        ],
        'api-rest' => [
            ActionRenderAbstractFactory::KEY_AR_MIDDLEWARE => [
                ActionRenderAbstractFactory::KEY_ACTION_MIDDLEWARE_SERVICE => 'apiRestAction',
                ActionRenderAbstractFactory::KEY_RENDER_MIDDLEWARE_SERVICE => 'simpleHtmlJsonRenderer'
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
        ]
    ],
];
