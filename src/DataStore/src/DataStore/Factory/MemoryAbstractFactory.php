<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Factory;

use Interop\Container\ContainerInterface;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use rollun\datastore\DataStore\Memory;

/**
 * Create and return an instance of the array in Memory
 * This Factory depends on Container (which should return an 'config' as array)
 *
 * The configuration can contain:
 * <code>
 * 'DataStore' => [
 *     'TheMemoryStore' => [
 *         'class' => 'rollun\datastore\DataStore\Memory',
 *         'requiredColumns' => [, // optional
 *              'column1',
 *              'column2',
 *              // ...
 *          ],
 *     ]
 * ]
 * </code>
 *
 * Class MemoryAbstractFactory
 * @package rollun\datastore\DataStore\Factory
 */
class MemoryAbstractFactory extends DataStoreAbstractFactory
{
    public static $KEY_DATASTORE_CLASS = Memory::class;

    protected static $KEY_IN_CREATE = 0;

    const KEY_REQUIRED_COLUMNS = 'requiredColumns';

    /**
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  array $options
     * @return Memory
     * @throws DataStoreException
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        if ($this::$KEY_IN_CREATE) {
            throw new DataStoreException("Create will be called without pre call canCreate method");
        }

        $this::$KEY_IN_CREATE = 1;

        $config = $container->get('config');
        $serviceConfig = $config[self::KEY_DATASTORE][$requestedName];

        $requestedClassName = $serviceConfig[self::KEY_CLASS];
        $requireColumns = $serviceConfig[self::KEY_REQUIRED_COLUMNS] ?? [];

        $this::$KEY_IN_CREATE = 0;

        return new $requestedClassName($requireColumns);
    }
}
