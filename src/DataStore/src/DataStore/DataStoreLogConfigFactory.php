<?php


namespace rollun\datastore\DataStore;


use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class DataStoreLogConfigFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get("config");
        $logConfig = $config[DataStoreLogConfig::class] ?? null;

        $dataStoreLogConfig = new DataStoreLogConfig();

        if (!is_array($logConfig)) {
            return $dataStoreLogConfig;
        }

        $dataStoreLogConfig->initFromConfig($logConfig);

        return $dataStoreLogConfig;
    }
}