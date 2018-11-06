<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Factory;

use Interop\Container\ContainerInterface;
use rollun\datastore\DataStore\CsvBase;
use Symfony\Component\Filesystem\LockHandler;
use rollun\datastore\DataStore\DataStoreException;

class CsvAbstractFactory extends DataStoreAbstractFactory
{
    const KEY_FILENAME = 'filename';
    const KEY_DELIMITER = 'delimiter';

    public static $KEY_DATASTORE_CLASS = CsvBase::class;

    protected static $KEY_IN_CREATE = 0;

    /**
     * {@inheritdoc}
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

        if (!isset($serviceConfig[self::KEY_FILENAME])) {
            $this::$KEY_IN_CREATE = 0;

            throw new DataStoreException(
                "The file name for '$requestedName' is not specified in the config 'dataStore'"
            );
        }

        $filename = $serviceConfig[self::KEY_FILENAME];
        $delimiter = isset($serviceConfig[self::KEY_DELIMITER]) ? $serviceConfig[self::KEY_DELIMITER] : null;
        $lockHandler = new LockHandler($filename);

        $this::$KEY_IN_CREATE = 0;

        return new $requestedClassName($filename, $delimiter, $lockHandler);
    }
}
