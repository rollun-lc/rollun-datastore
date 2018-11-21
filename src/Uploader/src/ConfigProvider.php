<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\uploader;

use rollun\uploader\Callback\Factory\UploaderAbstractFactory;

/**
 * The configuration provider for the App module
 *
 * @see https://docs.zendframework.com/zend-component-installer/
 */
class ConfigProvider
{
    /**
     * Returns the configuration array'TableManagerMysql'
     *
     * To add a bit of a structure, each section is defined in a separate
     * method which returns an array with its configuration.
     *
     * @return array
     */
    public function __invoke()
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    public function getDependencies()
    {
        return [
            'abstract_factories' => [
                UploaderAbstractFactory::class
            ],
        ];
    }
}
