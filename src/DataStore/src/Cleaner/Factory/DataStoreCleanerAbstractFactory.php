<?php

namespace rollun\datastore\Cleaner\Factory;

use Psr\Container\ContainerInterface;
use rollun\datastore\Cleaner\Cleaner;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;

class DataStoreCleanerAbstractFactory implements AbstractFactoryInterface
{
    const KEY = DataStoreCleanerAbstractFactory::class;

    const KEY_CLASS = "class";

    const KEY_DEFAULT_CLASS = Cleaner::class;

    const KEY_DATA_STORE_SERVICE = "dataStoreService";

    const KEY_CLEANING_VALIDATOR_SERVICE = "cleaningValidatorService";

    /**
     * Can the factory create an instance for the service?
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @return bool
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        $config = $container->get("config");
        return (
            isset($config[static::KEY][$requestedName][static::KEY_CLASS]) &&
            is_a($config[static::KEY][$requestedName][static::KEY_CLASS], static::KEY_DEFAULT_CLASS, true)
        );
    }

    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get("config");
        $serviceConfig = $config[static::KEY][$requestedName];
        $dataStore = $container->get($serviceConfig[static::KEY_DATA_STORE_SERVICE]);
        $cleaningValidator = $container->get($serviceConfig[static::KEY_CLEANING_VALIDATOR_SERVICE]);
        return new Cleaner($dataStore, $cleaningValidator);
    }
}
