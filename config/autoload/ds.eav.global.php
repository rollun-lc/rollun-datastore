<?php

/**
 * Created by PhpStorm.
 * User: root
 * Date: 19.10.16
 * Time: 17:19
 */
use rolluncom\datastore\DataStore\Eav\EavAbstractFactory;

return [
    'services' => [
        'aliases' => [
            EavAbstractFactory::DB_SERVICE_NAME => getenv('APP_ENV') === 'prod' ? 'db' : 'db',
        ],
        'abstract_factories' => [
            EavAbstractFactory::class,
        ]
    ],
];
