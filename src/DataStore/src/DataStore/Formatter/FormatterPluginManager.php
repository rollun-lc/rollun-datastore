<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Formatter;

use Laminas\ServiceManager\AbstractPluginManager;
use Laminas\ServiceManager\Factory\InvokableFactory;

class FormatterPluginManager extends AbstractPluginManager
{
    protected $instanceOf = FormatterInterface::class;

    protected $sharedByDefault = true;

    protected $factories = [
        BooleanFormatter::class => InvokableFactory::class,
        CharFormatter::class => InvokableFactory::class,
        StringFormatter::class => InvokableFactory::class,
        FloatFormatter::class => InvokableFactory::class,
        IntFormatter::class => InvokableFactory::class,
    ];

    protected $aliases = [
        'boolean' => BooleanFormatter::class,
        'bool' => BooleanFormatter::class,
        'integer' => IntFormatter::class,
        'int' => IntFormatter::class,
        'float' => FloatFormatter::class,
        'double' => FloatFormatter::class,
        'string' => StringFormatter::class,
        'char' => CharFormatter::class,
    ];
}
