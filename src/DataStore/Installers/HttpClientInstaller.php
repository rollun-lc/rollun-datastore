<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 11.03.17
 * Time: 11:55 AM
 */

namespace rollun\datastore\DataStore\Installers;


use rollun\datastore\DataStore\Factory\HttpClientAbstractFactory;
use rollun\datastore\Middleware\Factory\DataStoreAbstractFactory;
use rollun\installer\Install\InstallerAbstract;

class HttpClientInstaller extends InstallerAbstract
{

    /**
     * install
     * @return array
     */
    public function install()
    {
        return [
            'services' => [
                'abstract_factories' => [
                    HttpClientAbstractFactory::class,
                ],
            ]
        ];
    }

    /**
     * Clean all installation
     * @return void
     */
    public function uninstall()
    {

    }

    public function isInstall()
    {
        $config = $this->container->get('config');
        return (isset($config['services']['abstract_factories']) &&
            in_array(HttpClientAbstractFactory::class, $config['services']['abstract_factories']));
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
                $description = "Позволяет представить удаленный ресурс в качестве хранилища.";
                break;
            default:
                $description = "Does not exist.";
        }
        return $description;
    }
}