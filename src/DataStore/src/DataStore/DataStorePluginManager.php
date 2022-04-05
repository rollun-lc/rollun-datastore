<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore;

use rollun\datastore\DataStore\Interfaces\DataStoreInterface;
use Zend\ServiceManager\AbstractPluginManager;

class DataStorePluginManager extends AbstractPluginManager
{
    protected $instanceOf = DataStoreInterface::class;
}
