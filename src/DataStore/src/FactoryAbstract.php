<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace rollun\datastore;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Interop\Container\ContainerInterface;

abstract class FactoryAbstract implements FactoryInterface
{

    /**
     * Alias for "createService"
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return mixed
     */
    abstract public function __invoke(ContainerInterface $container, $requestedName, array $options = null);

    /**
     * {@inherit}
     *
     * {@inherit}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return $this->__invoke($serviceLocator, null);
    }

}
