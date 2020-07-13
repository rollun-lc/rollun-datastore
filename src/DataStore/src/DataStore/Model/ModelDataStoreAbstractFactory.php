<?php


namespace rollun\datastore\DataStore\Model;


use Interop\Container\ContainerInterface;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\Factory\DataStoreAbstractFactory;

class ModelDataStoreAbstractFactory extends DataStoreAbstractFactory
{
    public const KEY_MODEL = 'model';

    public static $KEY_DATASTORE_CLASS = ModelDataStore::class;

    protected static $KEY_IN_CREATE = 0;

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        if ($this::$KEY_IN_CREATE) {
            throw new DataStoreException("Create will be called without pre call canCreate method");
        }

        $this::$KEY_IN_CREATE = 1;

        $config = $container->get('config');
        $serviceConfig = $config[self::KEY_DATASTORE][$requestedName];
        $requestedClassName = $serviceConfig[self::KEY_CLASS];

        $dataStoreClassName = $serviceConfig[self::KEY_DATASTORE];
        $dataStore = $container->get($dataStoreClassName);
        $model = $serviceConfig[self::KEY_MODEL];

        $this::$KEY_IN_CREATE = 0;

        return new $requestedClassName($dataStore, $model);
    }
}