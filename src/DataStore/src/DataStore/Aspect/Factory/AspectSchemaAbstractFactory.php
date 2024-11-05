<?php

declare(strict_types=1);

namespace rollun\datastore\DataStore\Aspect\Factory;

use Psr\Container\ContainerInterface;
use rollun\datastore\DataStore\Aspect\AspectSchema;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\Entity\EntityFactory;
use rollun\datastore\DataStore\Factory\DataStoreAbstractFactory;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use rollun\datastore\DataStore\Query\NullQueryAdapter;
use rollun\datastore\DataStore\Query\QueryAdapter;
use rollun\datastore\DataStore\Scheme\Scheme;

class AspectSchemaAbstractFactory extends DataStoreAbstractFactory
{
    public const KEY_SCHEME = 'scheme';
    public const KEY_ENTITY_FACTORY = 'objectFactory';
    public const KEY_QUERY_ADAPTER = 'queryAdapter';

    protected static $KEY_DATASTORE_CLASS = AspectSchema::class;

    /**
     * @var string
     */
    private $requestedName;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var array
     */
    private $config;

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $this->init($container, $requestedName);
        $requestedClassName = $this->getFromConfig(static::KEY_CLASS);
        return new $requestedClassName(
            $this->getDataStore(),
            $this->getScheme(),
            $this->getEntityFactory(),
            $this->getQueryAdapter()
        );
    }

    private function init(ContainerInterface $container, string $requestedName)
    {
        $this->container = $container;
        $this->requestedName = $requestedName;
        $this->config = $container->get('config')[static::KEY_DATASTORE][$this->requestedName];
    }

    private function getDataStore(): DataStoresInterface
    {
        return $this->container->get($this->getFromConfig(DataStoreAbstractFactory::KEY_DATASTORE));
    }

    private function getScheme(): Scheme
    {
        $scheme = $this->getFromConfig(static::KEY_SCHEME);
        if (is_string($scheme)) {
            $scheme = $this->container->get($scheme);
        }
        if (!$scheme instanceof Scheme) {
            throw new DataStoreException('Scheme should be instance of ' . Scheme::class);
        }
        return $scheme;
    }

    /**
     * @throws DataStoreException
     */
    private function getEntityFactory(): EntityFactory
    {
        $objectFactory = $this->getFromConfig(static::KEY_ENTITY_FACTORY);
        if (is_string($objectFactory)) {
            $objectFactory = $this->container->get($objectFactory);
        }
        if (!$objectFactory instanceof EntityFactory) {
            throw new DataStoreException('Scheme should be instance of ' . EntityFactory::class);
        }
        return $objectFactory;
    }

    private function getQueryAdapter(): QueryAdapter
    {
        $queryAdapter =  $this->getFromConfig(static::KEY_QUERY_ADAPTER, true, new NullQueryAdapter());
        if (is_string($queryAdapter)) {
            $queryAdapter = $this->container->get($queryAdapter);
        }
        if (!$queryAdapter instanceof QueryAdapter) {
            throw new DataStoreException('Scheme should be instance of ' . EntityFactory::class);
        }
        return $queryAdapter;
    }

    private function getFromConfig(string $key, bool $optional = false, $default = null)
    {
        if (isset($this->config[$key])) {
            return $this->config[$key];
        }
        if ($optional) {
            return $default;
        }
        throw new DataStoreException("'$key' key is not specified for '$this->requestedName' datastore.");
    }
}
