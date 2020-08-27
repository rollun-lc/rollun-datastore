<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use rollun\datastore\DataStore\Aspect\AspectTyped;
use rollun\datastore\DataStore\DataStorePluginManager;
use Zend\Diactoros\Response\EmptyResponse;

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
     * Process an incoming server request and return a response, optionally delegating
     * response creation to a handler.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $requestedName = $request->getAttribute(ResourceResolver::RESOURCE_NAME);

        if (!$this->dataStorePluginManager->has($requestedName)) {
            return new EmptyResponse(404);
        }

        $dataStore = $this->dataStorePluginManager->get($requestedName);

        $dataStoreRest = new DataStoreRest($dataStore);
        $response = $dataStoreRest->process($request, $handler);

        $dataStoreScheme = $dataStore instanceof AspectTyped ? json_encode($dataStore->getScheme()) : '';

        // TODO
        $response = $response->withHeader('Datastore-Scheme', $dataStoreScheme);
        $response = $response->withHeader('X_DATASTORE_IDENTIFIER', $dataStore->getIdentifier());

        return $response;
    }
}
