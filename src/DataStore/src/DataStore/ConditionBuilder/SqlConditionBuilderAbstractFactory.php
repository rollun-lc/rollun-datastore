<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\ConditionBuilder;

use Psr\Container\ContainerInterface;
use InvalidArgumentException;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;

/**
 * Create and return an instance of the SqlConditionBuilder
 * This Factory depends on Container (which should return an 'config' as array)
 *
 * The configuration can contain:
 * <code>
 *  SqlConditionBuilderAbstractFactory::class => [
 *      'requestedServiceName1' => [
 *          'adapter' => 'db' // optional,
 *          'tableName' => 'someTableName'
 *      ],
 *      'requestedServiceName2' => [
 *          // ...
 *      ],
 *  ],
 * </code>
 *
 * Class SqlConditionBuilderAbstractFactory
 * @package rollun\datastore\TableGateway\Factory
 */
class SqlConditionBuilderAbstractFactory implements AbstractFactoryInterface
{
    public const KEY = self::class;

    public const DEFAULT_CLASS = SqlConditionBuilder::class;

    public const KEY_TABLE_NAME = 'tableName';

    public const KEY_ADAPTER = 'adapter';

    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @return bool
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        $config = $container->get('config');
        return isset($config[self::class][$requestedName]);
    }

    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return SqlConditionBuilder
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $serviceConfig = $container->get('config')[self::class][$requestedName];

        if (!isset($serviceConfig[self::KEY_TABLE_NAME])) {
            throw new InvalidArgumentException("Missing 'tableName' options");
        }

        $tableName = $container->get($serviceConfig[self::KEY_TABLE_NAME]);

        if (isset($serviceConfig[self::KEY_ADAPTER])) {
            $adapter = $container->get($serviceConfig[self::KEY_ADAPTER]);
        } else {
            $adapter = $container->get('db');
        }

        return new SqlConditionBuilder($adapter, $tableName);
    }
}
