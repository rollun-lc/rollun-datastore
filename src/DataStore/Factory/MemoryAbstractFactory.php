<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace rolluncom\datastore\DataStore\Factory;

use Interop\Container\ContainerInterface;
use rolluncom\datastore\AbstractFactoryAbstract;
use rolluncom\datastore\DataStore\DataStoreException;
use rolluncom\datastore\DataStore\Interfaces\DataStoresInterface;
use rolluncom\datastore\DataStore\Memory;

/**
 * Create and return an instance of the array in Memory
 *
 * This Factory depends on Container (which should return an 'config' as array)
 *
 * The configuration can contain:
 * <code>
 * 'DataStore' => [
 *     'TheMemoryStore' => [
 *         'class' => 'zaboy\rest\DataStore\Memory',
 *     ]
 * ]
 * </code>
 *
 * @category   rest
 * @package    zaboy
 */
class MemoryAbstractFactory extends AbstractDataStoreFactory
{

    public static $KEY_DATASTORE_CLASS = Memory::class;
    protected static $KEY_IN_CREATE = 0;

    /**
     * Create and return an instance of the DataStore.
     *
     * 'use Zend\ServiceManager\AbstractFactoryInterface;' for V2 to
     * 'use Zend\ServiceManager\Factory\AbstractFactoryInterface;' for V3
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  array $options
     * @return DataStoresInterface
     * @throws DataStoreException
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
         if($this::$KEY_IN_CREATE)
        {
            throw new DataStoreException("Create will be called without pre call canCreate method");
        }
        $this::$KEY_IN_CREATE = 1;

        $config = $container->get('config');
        $serviceConfig = $config[self::KEY_DATASTORE][$requestedName];
        $requestedClassName = $serviceConfig[self::KEY_CLASS];
        $this::$KEY_IN_CREATE = 0;
        return new $requestedClassName();

    }



}
