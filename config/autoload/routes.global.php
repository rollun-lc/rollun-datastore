<?php

return [
    'dependencies' => [
        'invokables' => [
            Zend\Expressive\Router\RouterInterface::class => Zend\Expressive\Router\FastRouteRouter::class,
        ],
        'factories' => [
            \rollun\datastore\Pipe\RestRql::class => \rollun\datastore\Pipe\Factory\RestRqlFactory::class
        ],
    ],

    'routes' => [
        [
            'name' => 'api.rest',
            'path' => '/api/rest[/{Resource-Name}[/{id}]]',
            'middleware' => \rollun\datastore\Pipe\RestRql::class,
            'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'],
        ],
    ],
];
