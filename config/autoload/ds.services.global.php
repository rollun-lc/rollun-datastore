<?php

use rolluncom\datastore\DataStore\Eav\EavAbstractFactory;
use rolluncom\datastore\TableGateway\Factory\TableManagerMysqlFactory;
use rolluncom\datastore\DataStore\Aspect\Factory\AspectAbstractFactory;
use rolluncom\datastore\Middleware\Factory\DataStoreAbstractFactory as MiddlewareDataStoreAbstractFactory;
use rolluncom\datastore\DataStore\Factory\HttpClientAbstractFactory;
use rolluncom\datastore\DataStore\Factory\DbTableAbstractFactory;
use rolluncom\datastore\DataStore\Factory\CsvAbstractFactory;
use rolluncom\datastore\DataStore\Factory\MemoryAbstractFactory;
use rolluncom\datastore\DataStore\Factory\CacheableAbstractFactory;
use Zend\Db\Adapter\AdapterAbstractServiceFactory;
use rolluncom\datastore\TableGateway\Factory\TableGatewayAbstractFactory;

return [

    'services' => [
        'factories' => [
            'TableManagerMysql' => TableManagerMysqlFactory::class
        ],
        'abstract_factories' => [
            EavAbstractFactory::class,
            AspectAbstractFactory::class,
            MiddlewareDataStoreAbstractFactory::class,
            HttpClientAbstractFactory::class,
            DbTableAbstractFactory::class,
            CsvAbstractFactory::class,
            MemoryAbstractFactory::class,
            CacheableAbstractFactory::class,
            AdapterAbstractServiceFactory::class,
            TableGatewayAbstractFactory::class,
        ],
        'aliases' => [
            EavAbstractFactory::DB_SERVICE_NAME => constant('APP_ENV') === 'prod' ? 'db' : 'db',
        ]
    ]
];
