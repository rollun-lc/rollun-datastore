<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\rest\DataStore\Installers;

use rollun\installer\Install\InstallerAbstract;
use rollun\rest\DataStore\Factory\HttpClientAbstractFactory;

class HttpClientInstaller extends InstallerAbstract
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
                    HttpClientAbstractFactory::class,
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

    public function isInstall()
    {
        $config = $this->container->get('config');

        return (isset($config['dependencies']['abstract_factories'])
            && in_array(HttpClientAbstractFactory::class, $config['dependencies']['abstract_factories']));
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
