<?php


namespace rollun\rest\Middleware\DataStoreRest;


use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use rollun\datastore\DataStore\Interfaces\RefreshableInterface;
use rollun\datastore\RestException;
use Zend\Diactoros\Response;

class RefreshHandler extends AbstractHandler
{

    /**
     * check if datastore rest middleware may handle this request
     * @param ServerRequestInterface $request
     * @return bool
     */
    protected function canHandle(ServerRequestInterface $request): bool
    {
        return $request->getMethod() === "PATCH";
    }

    /**
     * Handle request to dataStore;
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->dataStore instanceof RefreshableInterface) {
            $this->dataStore->refresh();

            $response = new Response();
            return $response;
        }
        throw new RestException("DataStore is not implement RefreshableInterface");
    }
}