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
use Psr\Log\LoggerInterface;
use rollun\datastore\DataStore\Aspect\AspectTyped;
use rollun\datastore\DataStore\DataStorePluginManager;
use Laminas\Diactoros\Response\EmptyResponse;
use rollun\datastore\DataStore\HttpClient;

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

    private LoggerInterface $logger;

    /**
     * Determinator constructor.
     * @param DataStorePluginManager $dataStorePluginManager
     */
    public function __construct(DataStorePluginManager $dataStorePluginManager, LoggerInterface $logger)
    {
        $this->dataStorePluginManager = $dataStorePluginManager;
        $this->logger = $logger;
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

        // feat(9wDgbMg5): log situations when ds is proxied from rollun-net-prvt to other service
        if ($dataStore instanceof HttpClient && $request->getUri()->getHost() !== 'rollun-net-front') {
            $this->logger->warning('Double proxy of datastore', [
                'request_host' => $request->getUri()->getHost(),
                'request_uri' => $request->getUri()->getPath(),
                'resource' => $requestedName,
            ]);
        }

        $dataStoreRest = new DataStoreRest($dataStore);
        $response = $dataStoreRest->process($request, $handler);

        $dataStoreScheme = $dataStore instanceof AspectTyped ? json_encode($dataStore->getScheme()) : '';

        // TODO
        $response = $response->withHeader('Datastore-Scheme', $dataStoreScheme);
        $response = $response->withHeader('X_DATASTORE_IDENTIFIER', $dataStore->getIdentifier());

        return $response;
    }
}
