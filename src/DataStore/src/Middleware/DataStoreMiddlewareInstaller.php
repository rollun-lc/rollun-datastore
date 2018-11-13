<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Middleware;

use rollun\datastore\DataStore\Aspect\Factory\AspectAbstractFactory;
use rollun\datastore\DataStore\DataStorePluginManager;
use rollun\datastore\DataStore\DataStorePluginManagerFactory;
use rollun\datastore\DataStore\Factory\CacheableAbstractFactory;
use rollun\datastore\DataStore\Factory\CsvAbstractFactory;
use rollun\datastore\DataStore\Factory\DbTableAbstractFactory;
use rollun\datastore\DataStore\Factory\HttpClientAbstractFactory;
use rollun\datastore\DataStore\Factory\MemoryAbstractFactory;
use rollun\datastore\Middleware\Factory\DataStoreApiFactory;
use rollun\datastore\Middleware\Factory\DeterminatorFactory;
use rollun\datastore\TableGateway\Factory\TableGatewayAbstractFactory;
use rollun\datastore\TableGateway\Factory\TableManagerMysqlFactory;
use rollun\datastore\TableGateway\TableManagerMysql;
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

                    TableGatewayAbstractFactory::class,
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

        $issetFactories = (isset($config['dependencies']['factories'])
            && in_array(ResourceResolver::class, $config['dependencies']['factories'])
            && in_array(RequestDecoder::class, $config['dependencies']['factories'])
            && in_array(Determinator::class, $config['dependencies']['factories'])
            && in_array(DataStoreApi::class, $config['dependencies']['factories'])
            && in_array(DataStorePluginManager::class, $config['dependencies']['factories'])
            && in_array(TableManagerMysql::class, $config['dependencies']['factories']));

        $issetAbstractFactories = (isset($config['dependencies']['abstract_factories'])
            && in_array(CacheableAbstractFactory::class, $config['dependencies']['abstract_factories'])
            && in_array(CsvAbstractFactory::class, $config['dependencies']['abstract_factories'])
            && in_array(DbTableAbstractFactory::class, $config['dependencies']['abstract_factories'])
            && in_array(HttpClientAbstractFactory::class, $config['dependencies']['abstract_factories'])
            && in_array(AspectAbstractFactory::class, $config['dependencies']['abstract_factories'])
            && in_array(TableGatewayAbstractFactory::class, $config['dependencies']['abstract_factories'])
            && in_array(MemoryAbstractFactory::class, $config['dependencies']['abstract_factories']));

        return $issetAbstractFactories && $issetFactories;
    }
}
