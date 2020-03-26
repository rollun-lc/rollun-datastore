<?php
declare(strict_types=1);

namespace rollun\datastore\DataStore\Factory;

use Interop\Container\ContainerInterface;
use Zend\EventManager\EventManager;
use Zend\ServiceManager\Factory\FactoryInterface;

/**
 * Class DataStoreEventManagerFactory
 *
 * @author Roman Ratsun <r.ratsun.rollun@gmail.com>
 */
class DataStoreEventManagerFactory implements FactoryInterface
{
    const ALIAS = 'dataStoreEventManager';

    const LISTENERS_KEY = 'listeners';

    const EVENT_KEY = 'dataStoreEvent';

    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /** @var array $config */
        $config = $container->get('config');

        // create event manager
        $eventManager = new EventManager();

        // attach listeners
        foreach ($config['dataStore'] as $dataStore => $data) {
            if (!empty($data[self::LISTENERS_KEY]) && is_array($data[self::LISTENERS_KEY])) {
                foreach ($data[self::LISTENERS_KEY] as $action => $handlers) {
                    $eventName = self::EVENT_KEY . '.' . $data['dataStore'] . '.' . $action;
                    if (!empty($handlers) && is_array($handlers)) {
                        foreach ($handlers as $handler) {
                            $eventManager->attach($eventName, (is_string($handler)) ? $container->get($handler) : $handler);
                        }
                    }
                }
            }
        }

        return $eventManager;
    }
}
