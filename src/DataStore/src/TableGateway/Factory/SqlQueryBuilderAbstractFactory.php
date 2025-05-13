<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\TableGateway\Factory;

use Psr\Container\ContainerInterface;
use InvalidArgumentException;
use rollun\datastore\TableGateway\SqlQueryBuilder;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;

/**
 * Create and return an instance of the SqlQueryBuilder
 * This Factory depends on Container (which should return an 'config' as array)
 *
 * The configuration can contain:
 * <code>
 *  SqlQueryBuilderAbstractFactory::class => [
 *      'adapter' => 'db' // optional,
 *      'sqlConditionBuilder' => 'sqlConditionBuilderService' // optional
 *      'tableName' => 'someTableName'
 *  ],
 * </code>
 *
 * Class SqlQueryBuilderAbstractFactory
 * @package rollun\datastore\TableGateway\Factory
 */
class SqlQueryBuilderAbstractFactory implements AbstractFactoryInterface
{
    public const KEY = self::class;

    public const DEFAULT_CLASS = SqlQueryBuilder::class;

    public const KEY_TABLE_NAME = 'tableName';

    public const KEY_SQL_CONDITION_BUILDER = 'sqlConditionBuilder';

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
     * @return SqlQueryBuilder
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $serviceConfig = $container->get('config')[self::class][$requestedName];

        if (!isset($serviceConfig[self::KEY_TABLE_NAME])) {
            throw new InvalidArgumentException("Missing 'tableName' options");
        }

        $tableName = $serviceConfig[self::KEY_TABLE_NAME];

        if (isset($serviceConfig[self::KEY_ADAPTER])) {
            $adapter = $container->get($serviceConfig[self::KEY_ADAPTER]);
        } else {
            $adapter = $container->get('db');
        }

        if (isset($serviceConfig[self::KEY_ADAPTER])) {
            $sqlConditionBuilder = $container->get($serviceConfig[self::KEY_SQL_CONDITION_BUILDER]);
        } else {
            $sqlConditionBuilder = null;
        }

        return new SqlQueryBuilder($adapter, $tableName, $sqlConditionBuilder);
    }
}
