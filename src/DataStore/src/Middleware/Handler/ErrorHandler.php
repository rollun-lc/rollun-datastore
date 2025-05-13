<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Middleware\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use rollun\datastore\Middleware\RestException;

/**
 * Rise exception if no one handler was executed.
 *
 * Class Validator
 * @package rollun\datastore\Middleware\Handler
 */
class ErrorHandler implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
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

        $response = $handler->handle($request);

        return $response;
    }
}
