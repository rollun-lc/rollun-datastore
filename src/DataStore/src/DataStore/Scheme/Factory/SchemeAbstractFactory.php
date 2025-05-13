<?php

declare(strict_types=1);

namespace rollun\datastore\DataStore\Scheme\Factory;

use Psr\Container\ContainerInterface;
use rollun\datastore\DataStore\Scheme\Scheme;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;

class SchemeAbstractFactory implements AbstractFactoryInterface
{
    public const KEY_SCHEMA = 'schema';


    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config')[self::KEY_SCHEMA][$requestedName];
        $fieldInfoFactory = new FieldInfoFactory($container);
        $schema = [];
        foreach ($config as $fieldName => $fieldInfo) {
            $schema[$fieldName] = $fieldInfoFactory->create($fieldInfo, $fieldName);
        }
        return new Scheme($schema);
    }

    public function canCreate(ContainerInterface $container, $requestedName)
    {
        return isset($container->get('config')[self::KEY_SCHEMA][$requestedName]);
    }

}
