<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Formatter;

use rollun\datastore\DataStore\Type\TypeInterface;
use rollun\datastore\DataStore\Type\TypePluginManager;
use Zend\ServiceManager\ServiceManager;

abstract class AbstractFormatter implements FormatterInterface
{
    /**
     * @var TypePluginManager
     */
    protected $typePluginManager;

    public function __construct($typePluginManager = null)
    {
        if (is_null($typePluginManager)) {
            $this->typePluginManager = new TypePluginManager(new ServiceManager());
        } else {
            $this->typePluginManager = $typePluginManager;
        }
    }

    /**
     * @param $type
     * @param $value
     * @return TypeInterface
     */
    protected function getTypeCaster($type, $value)
    {
        return $this->typePluginManager->get($type, ['value' => $value]);
    }
}
