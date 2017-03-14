<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 28.10.16
 * Time: 1:16 PM
 */

namespace rollun\datastore\DataStore\Composite;

use Composer\IO\IOInterface;
use Interop\Container\ContainerInterface;
use rollun\datastore\DataStore\Composite\Example\Store;
use rollun\datastore\DataStore\DbTable;
use rollun\datastore\TableGateway\DbSql\MultiInsertSql;
use rollun\installer\Install\InstallerAbstract;
use rollun\utils\DbInstaller;
use Zend\Db\Adapter\AdapterInterface;
use rollun\datastore\TableGateway\TableManagerMysql as TableManager;
use Zend\Db\TableGateway\TableGateway;

class CompositeInstaller extends InstallerAbstract
{
    /**
     *
     * @var AdapterInterface
     */
    private $dbAdapter;

    /**
     *
     *
     * Add to config:
     * <code>
     *    'services' => [
     *        'aliases' => [
     *            EavAbstractFactory::DB_SERVICE_NAME => getenv('APP_ENV') === 'prod' ? 'dbOnProduction' : 'local-db',
     *        ],
     *        'abstract_factories' => [
     *            EavAbstractFactory::class,
     *        ]
     *    ],
     * </code>
     * @param ContainerInterface $container
     * @param IOInterface $ioComposer
     */
    public function __construct(ContainerInterface $container, IOInterface $ioComposer)
    {
        parent::__construct($container, $ioComposer);
        //if ($this->container->has(Composite::DB_SERVICE_NAME)) {
            $this->dbAdapter = $this->container->get('db');
        /*} else {
            $this->consoleIO->write(Composite::DB_SERVICE_NAME . " not fount. It has did nothing");
        }*/
    }

    public function isInstall()
    {
        return $this->container->has(Composite::DB_SERVICE_NAME );

    }


    public function uninstall()
    {
        if (isset($this->dbAdapter)) {
            if (constant('APP_ENV') === 'dev') {
                $tableManager = new TableManager($this->dbAdapter);
                $tableManager->deleteTable(Store::IMAGE_TABLE_NAME);
                $tableManager->deleteTable(Store::CATEGORY_PRODUCT_TABLE_NAME);
                $tableManager->deleteTable(Store::PRODUCT_TABLE_NAME);
                $tableManager->deleteTable(Store::CATEGORY_TABLE_NAME);
            } else {
                $this->consoleIO->write('constant("APP_ENV") !== "dev" It has did nothing');
            }
        }
    }

    public function install()
    {
        if (isset($this->dbAdapter) && constant('APP_ENV') === 'dev') {
            //develop only
            $tablesConfigDevelop = [
                TableManager::KEY_TABLES_CONFIGS => Store::$develop_tables_config
            ];
            $tableManager = new TableManager($this->dbAdapter, $tablesConfigDevelop);
            $tableManager->rewriteTable(Store::PRODUCT_TABLE_NAME);
            $tableManager->rewriteTable(Store::IMAGE_TABLE_NAME);
            $tableManager->rewriteTable(Store::CATEGORY_TABLE_NAME);
            $tableManager->rewriteTable(Store::CATEGORY_PRODUCT_TABLE_NAME);
            $this->addData();
            return [
                'dataStore' => [
                    'product' => [
                        'class' => \rollun\datastore\DataStore\Composite\Composite::class,
                        'tableName' => 'product'
                    ],
                    'images' => [
                        'class' => \rollun\datastore\DataStore\Composite\Composite::class,
                        'tableName' => 'images'
                    ],
                    'category' => [
                        'class' => \rollun\datastore\DataStore\Composite\Composite::class,
                        'tableName' => 'category'
                    ],
                    'category_products' => [
                        'class' => \rollun\datastore\DataStore\Composite\Composite::class,
                        'tableName' => 'category_products'
                    ],
                ],
                'services' => [
                    'aliases' => [
                        Composite::DB_SERVICE_NAME => 'db',
                    ],
                ],
            ];
        }
        return [];
    }

    public function addData()
    {
        if (isset($this->dbAdapter)) {
            $data = array_merge(
                Store::$product,
                Store::$images,
                Store::$category,
                Store::$categoryProduct
            );

            foreach ($data as $key => $value) {
                $sql = new MultiInsertSql($this->dbAdapter, $key);
                $tableGateway = new TableGateway($key, $this->dbAdapter, null, null, $sql);
                $dataStore = new DbTable($tableGateway);
                $dataStore->create($value, true);
            }
        }
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
                $description = "Позволяет делать запросы по несокльким таблицам одновременно.";
                break;
            default:
                $description = "Does not exist.";
        }
        return $description;
    }

    public function getDependencyInstallers()
    {
        return [
            DbInstaller::class
        ];
    }
}