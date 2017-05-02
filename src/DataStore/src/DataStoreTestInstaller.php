<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 13.03.17
 * Time: 11:47
 */

namespace rollun\datastore;

use Composer\IO\IOInterface;
use Interop\Container\ContainerInterface;
use rollun\datastore\DataStore\Aspect\AspectInstaller;
use rollun\datastore\DataStore\Installers\CacheableInstaller;
use rollun\datastore\DataStore\Installers\CsvInstaller;
use rollun\datastore\DataStore\Installers\DbTableInstaller;
use rollun\datastore\DataStore\Installers\HttpClientInstaller;
use rollun\datastore\DataStore\Installers\MemoryInstaller;
use rollun\datastore\Middleware\DataStoreMiddlewareInstaller;
use rollun\datastore\TableGateway\DbSql\MultiInsertSql;
use rollun\installer\Install\InstallerAbstract;

class DataStoreTestInstaller extends InstallerAbstract
{
    protected $dataStore;

    protected $tableManagerMysql;

    protected $tableGateway;


    public function __construct(ContainerInterface $container, IOInterface $ioComposer)
    {
        parent::__construct($container, $ioComposer);
        $this->tableGateway = [
            'test_res_tablle' => [
                'sql' => MultiInsertSql::class,
            ],
            'table_with_name_same_as_resource_name' => [],
            'tbl_name_which_exist' => [],
            'test_res_http' => []
        ];
        $this->tableManagerMysql  = [
            'tablesConfigs' => [
                'test_table_config' => [],
            ],
            'autocreateTables' => [
                'test_autocreate_table' => 'test_table_config'
            ]
        ];
        $this->dataStore = [
            'test_DataStoreDbTableWithNameAsResourceName' => [
                'class' => \rollun\datastore\DataStore\DbTable::class,
                'tableName' => 'table_for_db_data_store'
            ],
            'test_StoreForMiddleware' => [
                'class' => \rollun\datastore\DataStore\Memory::class,
            ],
            'testDbTable' => [
                'class' => \rollun\datastore\DataStore\DbTable::class,
                'tableName' => 'test_res_tablle'
            ],

            'testDbTableMultiInsert' => [
                'class' => \rollun\datastore\DataStore\DbTable::class,
                'tableGateway' => 'test_res_tablle',
            ],
            'testHttpClient' => [
                'class' => \rollun\datastore\DataStore\HttpClient::class,
                'tableName' => 'test_res_http',
                'url' => 'http://' . constant("HOST") . '/api/datastore/test_res_http',
                'options' => ['timeout' => 30]
            ],
            'testMemory' => [
                'class' => \rollun\datastore\DataStore\Memory::class,
            ],
            'testCsvBase' => [
                'class' => \rollun\datastore\DataStore\CsvBase::class,
                'filename' => sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'testCsvBase.tmp',
                'delimiter' => ';',
            ],
            'testCsvIntId' => [
                'class' => \rollun\datastore\DataStore\CsvIntId::class,
                'filename' => sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'testCsvIntId.tmp',
                'delimiter' => ';',
            ],
            'testAspectAbstract' => [
                'class' => \rollun\datastore\DataStore\Aspect\AspectAbstract::class,
                'dataStore' => 'testMemory',
            ],

            'testDataSourceDb' => [
                'class' => \rollun\datastore\DataSource\DbTableDataSource::class,
                'tableName' => 'test_res_http'
            ],

            'testCacheable' => [
                'class' => \rollun\datastore\DataStore\Cacheable::class,
                'dataSource' => 'testDataSourceDb',
                'cacheable' => 'testDbTable'
            ]
        ];
    }

    /**
     * install
     * @return array
     */
    public function install()
    {
        return [
            'tableManagerMysql' => $this->tableManagerMysql,
            'tableGateway' => $this->tableGateway,
            'dataStore' => $this->dataStore,
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
            if(!$this->container->has($serviceName)) {
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
            DataStoreMiddlewareInstaller::class
        ];
    }


}
