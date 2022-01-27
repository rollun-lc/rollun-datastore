<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Factory;

use Interop\Container\ContainerInterface;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\HttpClient;
use Laminas\Http\Client;

/**
 * Create and return an instance of the DataStore which based on Http Client
 * Factory uses config from $container
 *
 * The configuration can contain:
 *
 * <code>
 * 'DataStore' => [
 *     'httpClient' => [
 *         'class' => 'rollun\datastore\DataStore\HttpDatastoreClassName',
 *          'url' => 'http://site.com/api/resource-name',
 *          'options' => [
 *              'identifier' => 'custom_id_field',
 *              'timeout' => 30,
 *              'adapter' => 'Laminas\Http\Client\Adapter\Socket',
 *          ]
 *     ]
 * ]
 * </code>
 *
 * Class HttpClientAbstractFactory
 * @package rollun\rest\DataStore\Factory
 */
class HttpClientAbstractFactory extends DataStoreAbstractFactory
{
    const KEY_URL = 'url';

    const KEY_OPTIONS = 'options';

    const KEY_HTTP_CLIENT = "httpClient";

    public static $KEY_DATASTORE_CLASS = HttpClient::class;

    protected static $KEY_IN_CREATE = 0;

    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return HttpClient
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

        // Packing arguments to array
        // Packing Client object
        if (isset($serviceConfig[self::KEY_HTTP_CLIENT])) {
            $arguments[] = $container->get($serviceConfig[self::KEY_HTTP_CLIENT]);
        } else {
            $arguments[] = new Client();
        }

        // Packing url
        if (isset($serviceConfig[self::KEY_URL])) {
            $arguments[] = $serviceConfig[self::KEY_URL];
        } else {
            $this::$KEY_IN_CREATE = 0;
            throw new DataStoreException(
                'There is not url for ' . $requestedName . 'in config \'dataStore\''
            );
        }

        // Packing options
        if (isset($serviceConfig[self::KEY_OPTIONS])) {
            $arguments[] = $serviceConfig[self::KEY_OPTIONS];
        }

        $result = new $requestedClassName(...$arguments);

        $this::$KEY_IN_CREATE = 0;

        return $result;
    }
}
