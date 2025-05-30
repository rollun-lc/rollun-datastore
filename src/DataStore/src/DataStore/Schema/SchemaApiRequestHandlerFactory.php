<?php

namespace rollun\datastore\DataStore\Schema;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class SchemaApiRequestHandlerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        return new SchemaApiRequestHandler($container->get(ArraySchemaRepository::class));
    }
}
