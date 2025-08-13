<?php

namespace rollun\datastore\Middleware\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response;

class HeadHandler extends AbstractHandler
{
    protected function canHandle(ServerRequestInterface $request): bool
    {
        return $request->getMethod() === "HEAD";
    }

    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        $response = new Response();

        $response = $response->withHeader('X_DATASTORE_IDENTIFIER', $this->dataStore->getIdentifier());

        if (method_exists($this->dataStore, 'multiCreate')) {
            $response = $response->withHeader('X_MULTI_CREATE', 'true');
        }

        if (method_exists($this->dataStore, 'queriedUpdate')) {
            $response = $response->withHeader('X_QUERIED_UPDATE', 'true');
        }

        return $response;
    }
}
