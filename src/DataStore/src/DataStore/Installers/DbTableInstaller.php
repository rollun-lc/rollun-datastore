<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Installers;

use rollun\datastore\DataStore\ConditionBuilder\SqlConditionBuilderAbstractFactory;
use rollun\datastore\DataStore\Factory\DbTableAbstractFactory;
use rollun\datastore\TableGateway\Factory\SqlQueryBuilderAbstractFactory;
use rollun\datastore\TableGateway\Factory\TableGatewayAbstractFactory;
use rollun\datastore\TableGateway\Factory\TableManagerMysqlFactory;
use rollun\installer\Install\InstallerAbstract;
use rollun\utils\DbInstaller;

class DbTableInstaller extends InstallerAbstract
{
    /**
     * install
     * @return array
     */
    public function install()
    {
        $config = [
            'dependencies' => [
                'factories' => [
                    'TableManagerMysql' => TableManagerMysqlFactory::class
                ],
                'abstract_factories' => [
                    DbTableAbstractFactory::class,
                    TableGatewayAbstractFactory::class,
                    SqlConditionBuilderAbstractFactory::class,
                    SqlQueryBuilderAbstractFactory::class,
                ],
            ]
        ];
        return $config;
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
                $description = "Позволяет представить таблицу в DB в качестве хранилища.";
                break;
            default:
                $description = "Does not exist.";
        }
        return $description;
    }

    public function isInstall()
    {
        $config = $this->container->get('config');
        //return false;
        $result = isset($config['dependencies']['abstract_factories']) &&
            isset($config['dependencies']['factories']) &&
            in_array(DbTableAbstractFactory::class, $config['dependencies']['abstract_factories']) &&
            in_array(TableGatewayAbstractFactory::class, $config['dependencies']['abstract_factories']) &&
            isset($config['dependencies']['factories']['TableManagerMysql']) &&
            $config['dependencies']['factories']['TableManagerMysql'] === TableManagerMysqlFactory::class;
        return $result;
    }

    public function getDependencyInstallers()
    {
        return [
            DbInstaller::class,
        ];
    }
}
