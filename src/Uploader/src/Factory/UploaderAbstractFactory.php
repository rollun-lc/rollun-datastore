<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\uploader\Factory;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use rollun\uploader\Uploader;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;

/**
 * Class UploaderAbstractFactory
 * @package rollun\uploader\Callback\Factory
 */
class UploaderAbstractFactory implements AbstractFactoryInterface
{
    const KEY = UploaderAbstractFactory::class;

    const KEY_SOURCE_DATA_ITERATOR_AGGREGATOR = "SourceDataIteratorAggregator";

    const KEY_DESTINATION_DATA_STORE = "DestinationDataStore";

    /**
     * Can the factory create an instance for the service?
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @return bool
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        try {
            $config = $container->get("config");
        } catch (NotFoundExceptionInterface|ContainerExceptionInterface $e) {
            return false;
        }

        return (isset($config[static::KEY][$requestedName]));
    }

    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return Uploader
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get("config");
        $serviceConfig = $config[static::KEY][$requestedName];
        $sourceDataIteratorAggregator = $container->get($serviceConfig[static::KEY_SOURCE_DATA_ITERATOR_AGGREGATOR]);
        $destinationDataStore = $container->get($serviceConfig[static::KEY_DESTINATION_DATA_STORE]);

        return new Uploader($sourceDataIteratorAggregator, $destinationDataStore);
    }
}
