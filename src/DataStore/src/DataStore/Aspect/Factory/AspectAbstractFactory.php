<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license   LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Aspect\Factory;

use Interop\Container\ContainerInterface;
use rollun\datastore\DataStore\Aspect\AbstractAspectListener;
use rollun\datastore\DataStore\Aspect\AspectAbstract;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\Factory\DataStoreAbstractFactory;
use rollun\datastore\DataStore\WithEventManagerInterface;
use Zend\EventManager\EventManager;

/**
 * Create and return an instance of the DataStore which based on AspectAbstract
 *
 * The configuration can contain:
 * <code>
 * 'dataStore' => [
 *     'real_service_name_for_aspect_datastore' => [
 *         'class' => 'rollun\datastore\DataStore\Aspect\AspectAbstract',
 *         'dataStore' => 'real_service_name_of_any_type_of_datastore'  // this service must be exist
 *         'listeners' => [\App\Listener\SomeListener::class, 'onPostCreate' => ['Callable1', 'Callable2']]
 *     ]
 * ]
 * </code>
 *
 * Class AspectAbstractFactory
 *
 * @package rollun\datastore\DataStore\Aspect\Factory
 */
class AspectAbstractFactory extends DataStoreAbstractFactory
{
    /**
     * Listener key
     */
    const KEY_LISTENERS = 'listeners';

    /**
     * All declared events of aspects
     */
    const EVENTS
        = [
            'onPreGetIterator',
            'onPostGetIterator',
            'onPreCreate',
            'onPostCreate',
            'onPreUpdate',
            'onPostUpdate',
            'onPreDelete',
            'onPostDelete',
            'onPreDeleteAll',
            'onPostDeleteAll',
            'onPreGetIdentifier',
            'onPostGetIdentifier',
            'onPreRead',
            'onPostRead',
            'onPreHas',
            'onPostHas',
            'onPreQuery',
            'onPostQuery',
            'onPreCount',
            'onPostCount',
            'onPreMultiCreate',
            'onPostMultiCreate',
            'onPreMultiUpdate',
            'onPostMultiUpdate',
            'onPreQueriedUpdate',
            'onPostQueriedUpdate',
            'onPreRewrite',
            'onPostRewrite',
            'onPreQueriedDelete',
            'onPostQueriedDelete',
        ];

    protected static $KEY_DATASTORE_CLASS = AspectAbstract::class;

    protected static $KEY_IN_CREATE = 0;

    /**
     * {@inheritdoc}
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');
        $serviceConfig = $config[static::KEY_DATASTORE][$requestedName];
        $requestedClassName = $serviceConfig[static::KEY_CLASS];

        if (!isset($serviceConfig['dataStore'])) {
            throw new DataStoreException(
                "The dataStore type for '$requestedName' is not specified in the config " . static::KEY_DATASTORE
            );
        }

        // create aspect with event manager if it needs
        if (is_a($requestedClassName, WithEventManagerInterface::class, true)) {
            // create event manager
            $eventManager = new EventManager();

            // attach listeners
            if (!empty($serviceConfig[self::KEY_LISTENERS]) && is_array($serviceConfig[self::KEY_LISTENERS])) {
                foreach ($serviceConfig[self::KEY_LISTENERS] as $k => $value) {
                    if (!empty($value)) {
                        // listener as class with events methods
                        if (is_string($value) && $container->has($value)) {
                            $service = $container->get($value);
                            if ($service instanceof AbstractAspectListener) {
                                foreach (self::EVENTS as $method) {
                                    $eventManager->attach($method, $service);
                                }
                            }
                        }

                        // listener is a callable class for certain event
                        if (is_array($value)) {
                            foreach ($value as $handler) {
                                $eventManager->attach($k, (is_string($handler)) ? $container->get($handler) : $handler);
                            }
                        }
                    }
                }
            }

            return new $requestedClassName($container->get($serviceConfig[DataStoreAbstractFactory::KEY_DATASTORE]), $eventManager, $serviceConfig['dataStore']);
        }

        return new $requestedClassName($container->get($serviceConfig[DataStoreAbstractFactory::KEY_DATASTORE]));
    }
}
