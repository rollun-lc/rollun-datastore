<?php

namespace rollun\datastore\DataStore\Schema;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

final class ArraySchemaRepositoryFactory implements FactoryInterface
{
    public const SCHEMAS = self::class . '::schemas';
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $config = $container->get('config');

        return new ArraySchemaRepository($config[self::SCHEMAS]);
    }
}
