<?php

namespace rollun\datastore\DataStore\Aspect\Factory;

use Interop\Container\ContainerInterface;
use rollun\datastore\AbstractFactoryAbstract;
use rollun\datastore\DataStore\Aspect\AspectAbstract;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\Factory\AbstractDataStoreFactory;

/**
 * Create and return an instance of the DataStore which based on AspectAbstract
 *
 * The configuration can contain:
 * <code>
 * 'DataStore' => [
 *
 *     'real_service_name_for_aspect_datastore' => [
 *         'class' => 'rollun\datastore\DataStore\Aspect\AspectAbstract',
 *         'dataStore' => 'real_service_name_of_any_type_of_datastore'  // this service must be exist
 *     ]
 * ]
 * </code>
 *
 * @category   rest
 * @package    zaboy
 */
class AspectAbstractFactory extends AbstractDataStoreFactory
{

    protected static $KEY_DATASTORE_CLASS = AspectAbstract::class;
    protected static $KEY_IN_CREATE = 0;

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');
        $serviceConfig = $config[static::KEY_DATASTORE][$requestedName];
        $requestedClassName = $serviceConfig[static::KEY_CLASS];
        if (!isset($serviceConfig['dataStore'])) {
            throw new DataStoreException(sprintf('The dataStore type for "%s" is not specified in the config "'
                . static::KEY_DATASTORE . '"', $requestedName));
        }
        $dataStore = $container->get($serviceConfig['dataStore']);
        return new $requestedClassName($dataStore);
    }

}