<?php

namespace rollun\datastore\Middleware\Handler;


use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class HeadHandler extends AbstractHandler
{

    protected function canHandle(ServerRequestInterface $request): bool
    {
        return $request->getMethod() === "HEAD";
    }

    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        $response = (new JsonResponse([]))
            ->withHeader('X_DATASTORE_IDENTIFIER', $this->dataStore->getIdentifier());

        if (method_exists($this->dataStore, 'multiCreate')) {
            $response = $response->withHeader('X_MULTI_CREATE', 'true');
        }

        return $response;
    }
}
