<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Middleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use rollun\datastore\DataStore\DataStorePluginManager;

/**
 * Class Determinator
 * @package rollun\datastore\Middleware
 */
class Determinator implements MiddlewareInterface
{
    /**
     * @var DataStorePluginManager
     */
    protected $dataStorePluginManager;

    /**
     * Determinator constructor.
     * @param DataStorePluginManager $dataStorePluginManager
     */
    public function __construct(DataStorePluginManager $dataStorePluginManager)
    {
        $this->dataStorePluginManager = $dataStorePluginManager;
    }

    /**
     * Simple hack to simplify determination data store and executing middleware with it
     *
     * @param ServerRequestInterface $request
     * @param DelegateInterface $delegate
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $requestedName = $request->getAttribute("resourceName");
        $dataStore = $this->dataStorePluginManager->get($requestedName);

        $dataStoreRest = new DataStoreRest($dataStore);
        $response = $dataStoreRest->process($request, $delegate);

        return $response;
    }
}
