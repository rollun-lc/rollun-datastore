<?php

namespace rollun\datastore\DataStore\Schema;

use Zend\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class SchemaApiRequestHandlerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        return new SchemaApiRequestHandler($container->get(ArraySchemaRepository::class));
    }
}
