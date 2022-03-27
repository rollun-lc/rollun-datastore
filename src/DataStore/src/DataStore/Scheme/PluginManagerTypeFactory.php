<?php

declare(strict_types=1);

namespace rollun\datastore\DataStore\Scheme;

use InvalidArgumentException;
use rollun\datastore\DataStore\Type\TypeInterface;
use rollun\datastore\DataStore\Type\TypePluginManager;
use Zend\ServiceManager\ServiceManager;

class PluginManagerTypeFactory implements TypeFactory
{
    /**
     * @var string
     */
    private $typeService;

    /**
     * @var TypePluginManager
     */
    private $typePluginManager;

    public function __construct(string $typeService, ?TypePluginManager $typePluginManager = null)
    {
        $this->typeService = $typeService;
        $this->typePluginManager = $typePluginManager ?? $this->getDefaultTypePluginManager();
        if (!$this->typePluginManager->has($typeService)) {
            throw new InvalidArgumentException('Plugin manager has not type service.');
        };
    }

    private function getDefaultTypePluginManager(): TypePluginManager
    {
        return new TypePluginManager(new ServiceManager());
    }

    public function create($value): TypeInterface
    {
        return $this->typePluginManager->get($this->typeService, ['value' => $value]);
    }
}