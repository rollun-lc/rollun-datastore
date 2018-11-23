<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore;

use Composer\IO\IOInterface;
use Interop\Container\ContainerInterface;
use rollun\datastore\DataSource\DbTableDataSource;
use rollun\datastore\DataStore\Aspect\AspectAbstract;
use rollun\datastore\DataStore\Aspect\AspectInstaller;
use rollun\datastore\DataStore\Cacheable;
use rollun\datastore\DataStore\ConditionBuilder\SqlConditionBuilder;
use rollun\datastore\DataStore\ConditionBuilder\SqlConditionBuilderAbstractFactory;
use rollun\datastore\DataStore\CsvBase;
use rollun\datastore\DataStore\CsvIntId;
use rollun\datastore\DataStore\DbTable;
use rollun\datastore\DataStore\Factory\DataStoreAbstractFactory;
use rollun\datastore\DataStore\HttpClient;
use rollun\datastore\DataStore\Installers\CacheableInstaller;
use rollun\datastore\DataStore\Installers\CsvInstaller;
use rollun\datastore\DataStore\Installers\DbTableInstaller;
use rollun\datastore\DataStore\Installers\HttpClientInstaller;
use rollun\datastore\DataStore\Installers\MemoryInstaller;
use rollun\datastore\DataStore\Memory;
use rollun\datastore\DataStore\SerializedDbTable;
use rollun\datastore\Middleware\DataStoreMiddlewareInstaller;
use rollun\datastore\TableGateway\Factory\SqlQueryBuilderAbstractFactory;
use rollun\datastore\TableGateway\Factory\TableGatewayAbstractFactory;
use rollun\datastore\TableGateway\SqlQueryBuilder;
use rollun\installer\Install\InstallerAbstract;

class DataStoreAssetInstaller extends InstallerAbstract
{
    protected $dataStore;

    protected $tableManagerMysql;

    protected $tableGateway;

    protected $sqlQueryBuilder;

    protected $sqlConditionBuilder;

    public function __construct(ContainerInterface $container, IOInterface $ioComposer)
    {
        parent::__construct($container, $ioComposer);

        $this->tableGateway = [
            'table_with_name_same_as_resource_name' => [],
            'tbl_name_which_exist' => [],
            'test_res_http' => [],

            // new tests
            'testTable' => [],
        ];

        $this->tableManagerMysql = [
            'tablesConfigs' => [
                'test_table_config' => [],
            ],
            'autocreateTables' => [
                'test_autocreate_table' => 'test_table_config',
            ],
        ];

        $this->dataStore = [
            'exploited1DbTable' => [
                'class' => DbTable::class,
                'tableName' => "test_exploit1_tablle",
            ],
            'exploited2DbTable' => [
                'class' => DbTable::class,
                'tableName' => "test_exploit2_tablle",
            ],
            'test_DataStoreDbTableWithNameAsResourceName' => [
                'class' => DbTable::class,
                'tableName' => 'table_for_db_data_store',
            ],
            'test_StoreForMiddleware' => [
                'class' => Memory::class,
            ],
            'testDbTable' => [
                'class' => DbTable::class,
                'tableName' => 'test_res_tablle',
            ],
            'testDbTableSerialized' => [
                'class' => SerializedDbTable::class,
                'tableName' => 'test_res_tablle',
                'sqlQueryBuilder' => SqlQueryBuilder::class,
            ],
            'testHttpClient' => [
                'class' => HttpClient::class,
                'tableName' => 'test_res_http',
                'url' => getenv('TEST_HOST') . 'api/datastore/testDataSourceDb',
                'options' => ['timeout' => 30],
            ],
            'testMemory' => [
                'class' => Memory::class,
            ],
            'testCsvBase' => [
                'class' => CsvBase::class,
                'filename' => sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'testCsvBase.tmp',
                'delimiter' => ';',
            ],
            'testCsvIntId' => [
                'class' => CsvIntId::class,
                'filename' => sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'testCsvIntId.tmp',
                'delimiter' => ';',
            ],
            'testAspectAbstract' => [
                'class' => AspectAbstract::class,
                'dataStore' => 'testMemory',
            ],
            'testDataSourceDb' => [
                'class' => DbTableDataSource::class,
                'tableName' => 'test_res_http',
            ],
            'testCacheable' => [
                'class' => Cacheable::class,
                'dataSource' => 'testDataSourceDb',
                'cacheable' => 'testDbTable',
            ],

            // new tests
            'memoryDataStore' => [
                'class' => Memory::class,
            ],
            'dbDataStore' => [
                'class' => DbTable::class,
                'tableGateway' => 'testTable',
            ],
            'dbDataStoreSerialized' => [
                'class' => SerializedDbTable::class,
                'tableName' => 'testTable',
                'sqlQueryBuilder' => 'sqlQueryBuilder1',
            ],
        ];

        $this->sqlQueryBuilder = [
            'sqlQueryBuilder1' => [
                'tableName' => 'test_res_tablle',
                'sqlConditionBuilder' => 'sqlConditionBuilder1',
            ],

            // new tests
            SqlQueryBuilder::class => [
                'tableName' => 'testTable',
                'sqlConditionBuilder' => SqlConditionBuilder::class,
            ],
        ];

        $this->sqlConditionBuilder = [
            'sqlConditionBuilder1' => [
                'tableName' => 'test_res_tablle',
            ],

            // new tests
            SqlConditionBuilder::class => [
                'tableName' => 'testTable',
            ],
        ];
    }

    /**
     * install
     * @return array
     */
    public function install()
    {
        return [
            TableGatewayAbstractFactory::KEY_TABLE_GATEWAY => $this->tableGateway,
            DataStoreAbstractFactory::KEY_DATASTORE => $this->dataStore,
            SqlQueryBuilderAbstractFactory::class => $this->sqlQueryBuilder,
            SqlConditionBuilderAbstractFactory::class => $this->sqlConditionBuilder,
            'tableManagerMysql' => $this->tableManagerMysql,
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
                $description = "Содержит набор данных для тестов.";
                break;
            default:
                $description = "Does not exist.";
        }

        return $description;
    }

    public function isInstall()
    {
        foreach ($this->dataStore as $serviceName => $config) {
            if (!$this->container->has($serviceName)) {
                return false;
            }
        }

        return true;
    }

    public function getDependencyInstallers()
    {
        return [
            CacheableInstaller::class,
            CsvInstaller::class,
            DbTableInstaller::class,
            HttpClientInstaller::class,
            MemoryInstaller::class,
            AspectInstaller::class,
            DataStoreMiddlewareInstaller::class,
        ];
    }
}
