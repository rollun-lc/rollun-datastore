<?php

declare(strict_types=1);

namespace rollun\datastore\DataStore\Scheme\Factory;

use Laminas\ServiceManager\ServiceManager;
use Psr\Container\ContainerInterface;
use rollun\datastore\DataStore\Formatter\FormatterInterface;
use rollun\datastore\DataStore\Formatter\NullFormatter;
use rollun\datastore\DataStore\Scheme\FieldInfo;
use rollun\datastore\DataStore\Scheme\Getter;
use rollun\datastore\DataStore\Scheme\PluginManagerTypeFactory;
use rollun\datastore\DataStore\Scheme\PropertyGetter;
use rollun\datastore\DataStore\Scheme\TypeFactory;
use rollun\datastore\DataStore\Type\TypePluginManager;

class FieldInfoFactory
{
    public const TYPE = 'type';
    public const FORMATTER = 'formatter';
    public const GETTER = 'getter';
    public const IS_NULLABLE = 'isNullable';

    public function __construct(private ContainerInterface $container)
    {
    }

    public function create(array $fieldInfo, string $fieldName): FieldInfo
    {
        return new FieldInfo(
            $this->resolveTypeFactory($fieldInfo),
            $this->resolveFormatter($fieldInfo),
            $this->resolveGetter($fieldInfo, $fieldName),
            $this->resolveIsNullable($fieldInfo)
        );
    }

    private function resolveTypeFactory(array $fieldInfo): TypeFactory
    {
        return new PluginManagerTypeFactory($fieldInfo[self::TYPE], new TypePluginManager(new ServiceManager()));
    }

    private function resolveFormatter(array $fieldInfo): FormatterInterface
    {
        $value = $fieldInfo[self::FORMATTER] ?? new NullFormatter();
        return is_string($value) ? $this->container->get($value) : $value;
    }

    private function resolveGetter(array $fieldInfo, string $fieldName): Getter
    {
        return $fieldInfo[self::GETTER] ?? new PropertyGetter($this->toCamelCase($fieldName));
    }

    private function toCamelCase(string $fieldName): string
    {
        return lcfirst(str_replace('_', '', ucwords($fieldName, '_')));
    }

    private function resolveIsNullable(array $fieldInfo)
    {
        return $fieldInfo[self::IS_NULLABLE] ?? false;
    }
}