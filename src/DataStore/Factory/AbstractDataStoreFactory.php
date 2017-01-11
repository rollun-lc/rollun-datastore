<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace rollun\datastore\DataStore\Factory;

use Interop\Container\ContainerInterface;
use rollun\datastore\AbstractFactoryAbstract;
use rollun\datastore\DataStore\DataStoreAbstract;
use rollun\datastore\DataStore\DataStoreException;

abstract class AbstractDataStoreFactory extends AbstractFactoryAbstract
{

    const KEY_DATASTORE = 'dataStore';

    protected static $KEY_DATASTORE_CLASS = DataStoreAbstract::class;
    protected static $KEY_IN_CANCREATE = 0;
    protected static $KEY_IN_CREATE = 0;

    /**
     * Can the factory create an instance for the service?
     * Use protection against circular dependencies (via static flags).
     * read https://github.com/avz-cmf/zaboy-rest/tree/master/src/DataStore/Factory/README.md
     * For Service manager V3
     * Edit 'use' section if need:
     * Change:
     * 'use Zend\ServiceManager\AbstractFactoryInterface;' for V2 to
     * 'use Zend\ServiceManager\Factory\AbstractFactoryInterface;' for V3
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @return bool
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        if (static::$KEY_IN_CANCREATE || static::$KEY_IN_CREATE) {
            return false;
        }
        static::$KEY_IN_CANCREATE = 1;
        $config = $container->get('config');
        if (!isset($config[static::KEY_DATASTORE][$requestedName][static::KEY_CLASS])) {
            $result = false;
        } else {
            $requestedClassName = $config[static::KEY_DATASTORE][$requestedName][static::KEY_CLASS];
            $result = is_a($requestedClassName, static::$KEY_DATASTORE_CLASS, true);
        }
        $this::$KEY_IN_CANCREATE = 0;
        return $result;
    }

}
