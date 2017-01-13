<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace rollun\datastore\DataStore\Eav;

use Composer\IO\IOInterface;
use Interop\Container\ContainerInterface;
use rollun\installer\Install\InstallerAbstract;
use rollun\datastore\DataStore\DbTable;
use rollun\datastore\TableGateway\DbSql\MultiInsertSql;
use Zend\Db\Adapter\AdapterInterface;
use rollun\datastore\TableGateway\TableManagerMysql as TableManager;
use rollun\datastore\DataStore\Eav\SysEntities;
use rollun\datastore\DataStore\Eav\Example\StoreCatalog;
use rollun\datastore\DataStore\Eav\EavAbstractFactory;
use Zend\Db\TableGateway\TableGateway;

/**
 * Installer class
 *
 * @category   Zaboy
 * @package    zaboy
 */
class Installer extends InstallerAbstract
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
        $this->dbAdapter = $this->container->get(EavAbstractFactory::DB_SERVICE_NAME);
    }

    public function uninstall()
    {
        if (constant('APP_ENV') !== 'dev') {
            $this->io->write('constant("APP_ENV") !== "dev" It has did nothing');
            exit;
        }

        $tableManager = new TableManager($this->dbAdapter);
        $tableManager->deleteTable(StoreCatalog::PROP_LINKED_URL_TABLE_NAME);
        $tableManager->deleteTable(StoreCatalog::PROP_PRODUCT_CATEGORY_TABLE_NAME);
        $tableManager->deleteTable(StoreCatalog::PROP_TAG_TABLE_NAME);
        $tableManager->deleteTable(StoreCatalog::MAIN_SPECIFIC_TABLE_NAME);
        $tableManager->deleteTable(StoreCatalog::MAINICON_TABLE_NAME);
        $tableManager->deleteTable(StoreCatalog::PRODUCT_TABLE_NAME);
        $tableManager->deleteTable(StoreCatalog::CATEGORY_TABLE_NAME);
        $tableManager->deleteTable(StoreCatalog::TAG_TABLE_NAME);
        $tableManager->deleteTable(SysEntities::TABLE_NAME);
    }

    public function install()
    {
        if (strcmp($this->io->ask("You wont create EAV tables ?(Need for test)[Yes/No]", "No"), 'Yes') === 0) {
            if (constant('APP_ENV') === 'dev') {
                //develop only
                $tablesConfigDevelop = [
                    TableManager::KEY_TABLES_CONFIGS => array_merge(
                        SysEntities::getTableConfigProdaction(),
                        StoreCatalog::$develop_tables_config
                    )
                ];
                $tableManager = new TableManager($this->dbAdapter, $tablesConfigDevelop);

                $tableManager->rewriteTable(SysEntities::TABLE_NAME);
                $tableManager->rewriteTable(StoreCatalog::PRODUCT_TABLE_NAME);
                $tableManager->rewriteTable(StoreCatalog::TAG_TABLE_NAME);
                $tableManager->rewriteTable(StoreCatalog::MAINICON_TABLE_NAME);
                $tableManager->rewriteTable(StoreCatalog::MAIN_SPECIFIC_TABLE_NAME);
                $tableManager->rewriteTable(StoreCatalog::CATEGORY_TABLE_NAME);
                $tableManager->rewriteTable(StoreCatalog::PROP_LINKED_URL_TABLE_NAME);
                $tableManager->rewriteTable(StoreCatalog::PROP_PRODUCT_CATEGORY_TABLE_NAME);
                $tableManager->rewriteTable(StoreCatalog::PROP_TAG_TABLE_NAME);
                if (strcmp($this->io->ask("You wont add data in EAV tables (Need for test)[Yes/No] ?", "No"), "Yes") === 0) {
                    $this->addData();
                }
            } else {
                $tablesConfigProdaction = [
                    TableManager::KEY_TABLES_CONFIGS => SysEntities::getTableConfigProdaction()
                ];
                $tableManager = new TableManager($this->dbAdapter, $tablesConfigProdaction);

                $tableManager->createTable(SysEntities::TABLE_NAME);
            }
        }
    }

    public function addData()
    {
        $data = array_merge(
            StoreCatalog::$sys_entities,
            StoreCatalog::$entity_product,
            StoreCatalog::$entity_category,
            StoreCatalog::$entity_tag,
            StoreCatalog::$entity_mainicon,
            StoreCatalog::$entity_main_specific,
            StoreCatalog::$prop_tag,
            StoreCatalog::$prop_product_category,
            StoreCatalog::$prop_linked_url
        );

        foreach ($data as $key => $value) {
            $sql = new MultiInsertSql($this->dbAdapter, $key);
            $tableGateway = new TableGateway($key, $this->dbAdapter, null, null, $sql);
            $dataStore = new DbTable($tableGateway);
            echo "create $key" . PHP_EOL;
            $dataStore->create($value, true);
        }
    }
}
