<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\rest\Middleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use rollun\datastore\DataStore\DataStorePluginManager;

class Determinator implements MiddlewareInterface
{
    /**
     * @var DataStorePluginManager
     */
    protected $dataStorePluginManager;

    public function __construct(DataStorePluginManager $dataStorePluginManager)
    {
        $this->dataStorePluginManager = $dataStorePluginManager;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $requestedName = $request->getAttribute("resourceName");
        $dataStore = $this->dataStorePluginManager->get($requestedName);

        $dataStoreRest = new DataStoreRest($dataStore);
        $response = $dataStoreRest->process($request, $delegate);

        return $response;
    }
}
