<?php


namespace rollun\rest\Middleware\DataStoreRest;


use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use rollun\actionrender\Renderer\AbstractRenderer;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;

class DeleteHandler extends AbstractHandler
{
    /**
     * check if datastore rest middleware may handle this request
     * @param ServerRequestInterface $request
     * @return bool
     */
    protected function canHandle(ServerRequestInterface $request): bool
    {
        $canHandle = $request->getMethod() === "DELETE";

        $primaryKeyValue = $request->getAttribute('primaryKeyValue');
        $canHandle = $canHandle && isset($primaryKeyValue);

        return $canHandle;
    }

    /**
     * Handle request to dataStore;
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        $primaryKeyValue = $request->getAttribute('primaryKeyValue');
        $items = $this->dataStore->delete($primaryKeyValue);

        $response = new Response();

        if (!isset($items)) {
            $response = $response->withStatus(204);
        }
        $stream = fopen("data://text/plain;base64,".base64_encode(serialize($items)), 'r');
        $response = $response->withBody(new Stream($stream));

        return $response;
    }
}