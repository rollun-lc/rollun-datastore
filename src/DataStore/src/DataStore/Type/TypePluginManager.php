<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Type;

use Laminas\ServiceManager\AbstractPluginManager;

class TypePluginManager extends AbstractPluginManager
{
    protected $instanceOf = TypeInterface::class;

    protected $factories = [
        TypeFloat::class => TypeFactory::class,
        TypeInt::class => TypeFactory::class,
        TypeChar::class => TypeFactory::class,
        TypeString::class => TypeFactory::class,
        TypeBoolean::class => TypeFactory::class,
    ];

    protected $aliases = [
        'boolean' => TypeBoolean::class,
        'bool' => TypeBoolean::class,
        'integer' => TypeInt::class,
        'int' => TypeInt::class,
        'float' => TypeFloat::class,
        'double' => TypeFloat::class,
        'string' => TypeString::class,
        'char' => TypeChar::class,
    ];
}
