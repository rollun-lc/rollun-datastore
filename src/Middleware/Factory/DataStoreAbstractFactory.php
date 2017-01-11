<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace rollun\datastore\Middleware\Factory;

use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\Middleware\DataStoreAbstract;
use Zend\Stratigility\MiddlewareInterface;
use Interop\Container\ContainerInterface;
use rollun\datastore\AbstractFactoryAbstract;

/**
 * Factory for middleware which contane DataStore
 *
 * config
 * <code>
 *  'middleware' => [
 *      'MiddlewareName' => [
 *          'class' =>'rollun\datastore\MiddlewareType',
 *          'dataStore' => 'rollun\datastore\DataStore\Type'
 *      ],
 *      'MiddlewareAnotherName' => [
 *          'class' =>'rollun\datastore\MiddlewareAnotherType',
 *          'dataStore' => 'rollun\datastore\DataStore\AnotherType'
 *      ],
 *  ...
 *  ],
 * </code>
 * @category   rest
 * @package    zaboy
 */
class DataStoreAbstractFactory extends AbstractFactoryAbstract
{

    /**
     * Can the factory create an instance for the service?
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @return bool
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        $config = $container->get('config');
        $isClassName = isset($config['middleware'][$requestedName]['class']);
        if ($isClassName) {
            $requestedClassName = $config['middleware'][$requestedName]['class'];
            return is_a($requestedClassName, DataStoreAbstract::class, true);
        } else {
            return false;
        }
    }

    /**
     * Create and return an instance of the Middleware.
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  array $options
     * @return MiddlewareInterface
     * @throws DataStoreException
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');
        $serviceConfig = $config['middleware'][$requestedName];
        $requestedClassName = $serviceConfig['class'];
        //take store for Middleware
        $dataStoreServiceName = isset($serviceConfig['dataStore']) ? $serviceConfig['dataStore'] : null;
        if (!($container->get($dataStoreServiceName))) {
            throw new DataStoreException(
                'Can\'t get Store' . $dataStoreServiceName . ' for Middleware ' . $requestedName
            );
        }
        $dataStore = $container->get($dataStoreServiceName);
        return new $requestedClassName($dataStore);
    }

}
