<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore;

use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use Laminas\ServiceManager\AbstractPluginManager;

class DataStorePluginManager extends AbstractPluginManager
{
    protected $instanceOf = DataStoresInterface::class;
}
