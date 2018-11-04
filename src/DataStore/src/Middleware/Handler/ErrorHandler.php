<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Middleware\Handler;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use rollun\datastore\Middleware\RestException;

/**
 * Rise exception if no one handler was executed.
 *
 * Class Validator
 * @package rollun\datastore\Middleware\Handler
 */
class ErrorHandler implements MiddlewareInterface
{
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
        $response = $request->getAttribute(ResponseInterface::class);

        if (!($response instanceof ResponseInterface)) {
            throw new RestException(
                "No one datastore handler was executed. "
                . "Method: '{$request->getMethod()}'. "
                . "Uri: '{$request->getUri()->getPath()}'. "
                . "ParsedBody: '" . json_encode($request->getParsedBody()) . "'. "
                . "Attributes: '" . json_encode($request->getAttributes()) . "'. "
            );
        }

        $response = $delegate->process($request);

        return $response;
    }
}
