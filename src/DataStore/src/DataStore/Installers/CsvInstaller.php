<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Installers;

use rollun\datastore\DataStore\Factory\CsvAbstractFactory;
use rollun\installer\Install\InstallerAbstract;

class CsvInstaller extends InstallerAbstract
{
    /**
     * install
     * @return array
     */
    public function install()
    {
        return [
            'dependencies' => [
                'abstract_factories' => [
                    CsvAbstractFactory::class,
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

    /**
     * Return string with description of installable functional.
     * @param string $lang ; set select language for description getted.
     * @return string
     */
    public function getDescription($lang = "en")
    {
        switch ($lang) {
            case "ru":
                $description = "Позволяет представить csv таблицу в кчестве хранилища";
                break;
            default:
                $description = "Does not exist.";
        }
        return $description;
    }

    public function isInstall()
    {
        $config = $this->container->get('config');
        return (isset($config['dependencies']['abstract_factories']) &&
            in_array(CsvAbstractFactory::class, $config['dependencies']['abstract_factories']));
    }
}
