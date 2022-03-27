<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore;

use rollun\datastore\DataStore\Aspect\Factory\AspectAbstractFactory;
use rollun\datastore\DataStore\Aspect\Factory\AspectSchemaAbstractFactory;
use rollun\datastore\DataStore\ConditionBuilder\SqlConditionBuilderAbstractFactory;
use rollun\datastore\DataStore\DataStorePluginManager;
use rollun\datastore\DataStore\DataStorePluginManagerFactory;
use rollun\datastore\DataStore\Factory\CacheableAbstractFactory;
use rollun\datastore\DataStore\Factory\CsvAbstractFactory;
use rollun\datastore\DataStore\Factory\DbTableAbstractFactory;
use rollun\datastore\DataStore\Factory\HttpClientAbstractFactory;
use rollun\datastore\DataStore\Factory\MemoryAbstractFactory;
use rollun\datastore\DataStore\Scheme\Factory\SchemeAbstractFactory;
use rollun\datastore\Middleware\DataStoreApi;
use rollun\datastore\Middleware\Determinator;
use rollun\datastore\Middleware\Factory\DataStoreApiFactory;
use rollun\datastore\Middleware\Factory\DeterminatorFactory;
use rollun\datastore\Middleware\RequestDecoder;
use rollun\datastore\Middleware\ResourceResolver;
use rollun\datastore\TableGateway\Factory\SqlQueryBuilderAbstractFactory;
use rollun\datastore\TableGateway\Factory\TableGatewayAbstractFactory;
use rollun\datastore\TableGateway\Factory\TableManagerMysqlFactory;
use rollun\datastore\TableGateway\TableManagerMysql;
use Laminas\ServiceManager\Factory\InvokableFactory;

/**
 * The configuration provider for the App module
 *
 * @see https://docs.zendframework.com/zend-component-installer/
 */
class ConfigProvider
{
    /**
     * Returns the configuration array'TableManagerMysql'
     *
     * To add a bit of a structure, each section is defined in a separate
     * method which returns an array with its configuration.
     *
     * @return array
     */
    public function __invoke()
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    public function getDependencies()
    {
        return [
            'factories' => [
                ResourceResolver::class => InvokableFactory::class,
                RequestDecoder::class => InvokableFactory::class,
                Determinator::class => DeterminatorFactory::class,
                DataStoreApi::class => DataStoreApiFactory::class,
                DataStorePluginManager::class => DataStorePluginManagerFactory::class,

                'TableManagerMysql' => TableManagerMysqlFactory::class,
                TableManagerMysql::class => TableManagerMysqlFactory::class,
            ],
            'abstract_factories' => [
                // Data stores
                CacheableAbstractFactory::class,
                CsvAbstractFactory::class,
                DbTableAbstractFactory::class,
                HttpClientAbstractFactory::class,
                MemoryAbstractFactory::class,

                // Aspects
                AspectAbstractFactory::class,
                AspectSchemaAbstractFactory::class,

                // Scheme
                SchemeAbstractFactory::class,

                TableGatewayAbstractFactory::class,
                SqlConditionBuilderAbstractFactory::class,
                SqlQueryBuilderAbstractFactory::class,
            ],
        ];
    }
}
