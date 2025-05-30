<?php

namespace rollun\datastore\DataStore\Schema;

use Interop\Container\Containerinterface;
use Zend\ServiceManager\Factory\FactoryInterface;

final class ArraySchemaRepositoryFactory implements FactoryInterface
{
    public const SCHEMAS = self::class . '::schemas';
    public function __invoke(Containerinterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');

        return new ArraySchemaRepository($config[self::SCHEMAS]);
    }
}
