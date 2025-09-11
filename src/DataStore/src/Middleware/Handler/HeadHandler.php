<?php


namespace rollun\datastore\Middleware\Handler;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

class HeadHandler extends AbstractHandler
{

    protected function canHandle(ServerRequestInterface $request): bool
    {
        return $request->getMethod() === "HEAD";
    }

    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        $response = new JsonResponse([]);

        $response = $response->withHeader('X_DATASTORE_IDENTIFIER', $this->dataStore->getIdentifier());

        if (method_exists($this->dataStore, 'multiCreate')) {
            $response = $response->withHeader('X_MULTI_CREATE', 'true');
        }

        return $response;
    }
}