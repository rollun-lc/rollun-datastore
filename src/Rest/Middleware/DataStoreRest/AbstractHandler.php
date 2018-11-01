<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\rest\Middleware\DataStoreRest;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use rollun\rest\Middleware\DataStoreAbstract;
use rollun\rest\Middleware\JsonRenderer;

abstract class AbstractHandler extends DataStoreAbstract
{
    /**
     * Check if datastore rest middleware may handle this request
     *
     * @param ServerRequestInterface $request
     * @return bool
     */
    abstract protected function canHandle(ServerRequestInterface $request): bool;

    /**
     * Handle request to dataStore
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    abstract protected function handle(ServerRequestInterface $request): ResponseInterface;

    /**
     * Process an incoming server request and return a response.
     * Optionally delegating to the next middleware component to create the response.
     *
     * @param ServerRequestInterface $request
     * @param DelegateInterface $delegate
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        if ($this->canHandle($request)) {
            $response = $this->handle($request);
            $stream = $response->getBody();

            if (isset($stream)) {
                $data = unserialize($stream->getContents());
                $request = $request->withAttribute(JsonRenderer::RESPONSE_DATA, $data);
            }

            $request = $request->withAttribute(ResponseInterface::class, $response);
        }

        $response = $delegate->process($request);

        return $response;
    }
}
