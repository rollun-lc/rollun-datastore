<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\TableGateway\Factory;

use Psr\Container\ContainerInterface;
use rollun\datastore\AbstractFactoryAbstract;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Metadata\Metadata;
use Laminas\Db\TableGateway\TableGateway;

/**
 * Create and return an instance of the TableGateway
 * This Factory depends on Container (which should return an 'config' as array)
 *
 * The configuration can contain:
 * <code>
 *  'tableGateway' => [
 *      'sql' => 'Laminas\Db\Sql\Sql', // optional
 *      'adapter' => 'db' // optional,
 *  ],
 * </code>
 *
 * Class TableGatewayAbstractFactory
 * @package rollun\datastore\TableGateway\Factory
 */
class TableGatewayAbstractFactory extends AbstractFactoryAbstract
{
    const KEY_SQL = 'sql';

    const KEY_TABLE_GATEWAY = 'tableGateway';

    const KEY_ADAPTER = 'adapter';

    /**
     * @var null|array
     */
    protected $tableNames = null;

    /**
     * @var Adapter
     */
    protected $db;

    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @return bool
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        $config = $container->get('config');

        if (!isset($config[self::KEY_TABLE_GATEWAY][$requestedName])) {
            return false;
        }

        if ($this->setDbAdapter($container, $requestedName)) {
            $dbMetadata = new Metadata($this->db);
            $this->tableNames = array_merge($dbMetadata->getTableNames(), $dbMetadata->getViewNames());
        }

        return is_array($this->tableNames) && in_array($requestedName, $this->tableNames, true);
    }

    /**
     *
     * @param ContainerInterface $container
     * @param $requestedName
     * @return bool
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function setDbAdapter(ContainerInterface $container, $requestedName)
    {
        $config = $container->get('config')[self::KEY_TABLE_GATEWAY];

        if (isset($config[$requestedName]) && isset($config[$requestedName][static::KEY_ADAPTER])) {
            $this->db = $container->has($config[$requestedName][static::KEY_ADAPTER])
                ? $container->get($config[$requestedName][static::KEY_ADAPTER])
                : false;
        } else {
            $this->db = $container->has('db') ? $container->get('db') : false;
        }

        return (bool)$this->db;
    }

    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return \rollun\datastore\DataStore\Interfaces\DataStoresInterface|TableGateway
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config')[self::KEY_TABLE_GATEWAY][$requestedName];

        if (isset($config[self::KEY_SQL]) && is_a($config[self::KEY_SQL], 'Laminas\Db\Sql\Sql', true)) {
            $sql = new $config[self::KEY_SQL]($this->db, $requestedName);

            return new TableGateway($requestedName, $this->db, null, null, $sql);
        }

        return new TableGateway($requestedName, $this->db);
    }
}
