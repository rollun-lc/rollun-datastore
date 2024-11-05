<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataSource\Factory;

use Psr\Container\ContainerInterface;
use rollun\datastore\DataSource\MemoryConfig;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;

/**
 * Create instance of MemoryConfig
 *
 * Class ConfigDataSourceAbstractFactory
 * @package rollun\permission\Acl\DataSource\Factory
 */
class ConfigDataSourceAbstractFactory implements AbstractFactoryInterface
{
    const KEY_DATASOURCE = 'dataSource';

    const KEY_CONFIG = 'config';

    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return MemoryConfig
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');
        $serviceConfig = $config[self::KEY_DATASOURCE][$requestedName];
        $confName = isset($serviceConfig[static::KEY_CONFIG]) ? $serviceConfig[static::KEY_CONFIG] : $requestedName;
        $data = $config[$confName];

        return new MemoryConfig($data);
    }

    /**
     * Can the factory create an instance for the service?
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @return bool
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        $config = $container->get('config');

        return isset($config[static::KEY_DATASOURCE][$requestedName]);
    }
}
