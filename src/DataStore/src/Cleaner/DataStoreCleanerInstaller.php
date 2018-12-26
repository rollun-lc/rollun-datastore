<?php


namespace rollun\datastore\Cleaner;


use rollun\datastore\Cleaner\Factory\DataStoreCleanerAbstractFactory;
use rollun\installer\Install\InstallerAbstract;

class DataStoreCleanerInstaller extends InstallerAbstract
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
                    DataStoreCleanerAbstractFactory::class,
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
                $description = "Предоставляет сервис для очистки DS.";
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
            in_array(DataStoreCleanerAbstractFactory::class, $config['dependencies']['abstract_factories']));
    }
}
