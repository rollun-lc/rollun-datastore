<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\TableGateway\Factory;

use Interop\Container\ContainerInterface;
use rollun\datastore\FactoryAbstract;
use rollun\datastore\TableGateway\TableManagerMysql;

/**
 * Create and return an instance of the TableGateway
 * This Factory depends on Container (which should return an 'config' as array)
 *
 * The configuration can contain:
 * <code>
 *  'tableGateway' => [
 *      'sql' => 'Zend\Db\Sql\Sql', // optional
 *      'adapter' => 'db' // optional,
 *  ],
 * </code>
 *
 * Class TableManagerMysqlFactory
 * @package rollun\datastore\TableGateway\Factory
 */
class TableManagerMysqlFactory extends FactoryAbstract
{
    /**
     * Create and return an instance of the TableGateway.
     *
     * 'use Zend\ServiceManager\AbstractFactoryInterface;' for V2 to
     * 'use Zend\ServiceManager\Factory\AbstractFactoryInterface;' for V3
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  array $options
     * @return mixed|TableManagerMysql
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $db = $container->get('db');

        if (!isset($options[TableManagerMysql::KEY_IN_CONFIG])) {
            $tableManagerConfig = [
                TableManagerMysql::KEY_AUTOCREATE_TABLES => [],
                TableManagerMysql::KEY_TABLES_CONFIGS => [],
            ];
        } else {
            $tableManagerConfig = $options[TableManagerMysql::KEY_IN_CONFIG];
        }

        $tableManager = new TableManagerMysql($db, $tableManagerConfig);

        return $tableManager;
    }
}
