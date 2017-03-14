<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 13.03.17
 * Time: 10:26
 */

namespace rollun\datastore\DataStore\Installers;

use rollun\datastore\DataStore\Factory\DbTableAbstractFactory;
use rollun\datastore\TableGateway\Factory\TableGatewayAbstractFactory;
use rollun\datastore\TableGateway\Factory\TableManagerMysqlFactory;
use rollun\installer\Install\InstallerAbstract;
use Zend\Db\Adapter\AdapterAbstractServiceFactory;

class DbTableInstaller extends InstallerAbstract
{

    /**
     * install
     * @return array
     */
    public function install()
    {
        $config = [
            'services' => [
                'factories' => [
                    'TableManagerMysql' => TableManagerMysqlFactory::class
                ],
                'abstract_factories' => [
                    DbTableAbstractFactory::class,
                    AdapterAbstractServiceFactory::class,
                    TableGatewayAbstractFactory::class,
                ],
            ]
        ];

        if ($this->consoleIO->askConfirmation("you want to add a configuration to connect to the database itself(else we generate it ourselves) ?", false)) {
            do {
                $this->consoleIO->write("You mast create config for db adapter, with adapter name 'db'.");
                $answer = $this->consoleIO->askConfirmation("Is the config file created?");
                if (!$answer || !$this->container->has('db')) {
                    $this->consoleIO->write("You not create correct config for adapter.");
                }
            } while (!$answer || !$this->container->has('db'));
        } else {
            $drivers = ['IbmDb2', 'Mysqli', 'Oci8', 'Pgsql', 'Sqlsrv', 'Pdo_Mysql', 'Pdo_Sqlite', 'Pdo_Pgsql'];
            $index = $this->consoleIO->select("", $drivers, 5);

            do {
                $dbName = $this->consoleIO->ask("Set database name:");
                if (is_null($dbName)) {
                    $this->consoleIO->write("You not set, database name");
                }
            } while ($dbName == null);
            do {
                $dbUser = $this->consoleIO->ask("Set database user name:");
                if (is_null($dbUser)) {
                    $this->consoleIO->write("You not set, database user name");
                }
            } while ($dbUser == null);
            $dbPass = $this->consoleIO->askAndHideAnswer("Set database password:");

            $config['db'] = [
                'adapters' => [
                    'db' => [
                        'driver' => $drivers[$index],
                        'database' => $dbName,
                        'username' => $dbUser,
                        'password' => $dbPass
                    ]
                ]
            ];
        }
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
        $result = isset($config['services']['abstract_factories']) &&
            isset($config['services']['factories']) &&
            $this->container->has('db') &&
            in_array(DbTableAbstractFactory::class, $config['services']['abstract_factories']) &&
            in_array(AdapterAbstractServiceFactory::class, $config['services']['abstract_factories']) &&
            in_array(TableGatewayAbstractFactory::class, $config['services']['abstract_factories']) &&
            isset($config['services']['factories']['TableManagerMysql']) &&
            $config['services']['factories']['TableManagerMysql'] === TableManagerMysqlFactory::class;
        return $result;
    }


}
