<?php


namespace rollun\datastore\Middleware\DataStoreRest;


use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use rollun\actionrender\Renderer\AbstractRenderer;
use rollun\datastore\RestException;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;

class UpdateHandler extends AbstractHandler
{
    /**
     * check if datastore rest middleware may handle this request
     * @param ServerRequestInterface $request
     * @return bool
     */
    protected function canHandle(ServerRequestInterface $request): bool
    {
        $canHandle = $request->getMethod() === "PUT";

        $primaryKeyValue = $request->getAttribute('primaryKeyValue');
        $canHandle = $canHandle && isset($primaryKeyValue);

        $row = $request->getParsedBody();
        $canHandle = $canHandle && isset($row) && is_array($row) && array_reduce(array_keys($row), function ($carry, $item) {
                return $carry && !is_integer($item);
            }, true);
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
        $primaryKeyIdentifier = $this->dataStore->getIdentifier();
        $item = $request->getParsedBody();

        $item = array_merge(array($primaryKeyIdentifier => $primaryKeyValue), $item);
        $overwriteMode = $request->getAttribute('overwriteMode');
        $isIdExist = !empty($this->dataStore->read($primaryKeyValue));


        $response = new Response();
        $newItem = $this->dataStore->update($item, $overwriteMode);

        $stream = fopen("data://text/plain;base64,".base64_encode(serialize($newItem)), 'r');
        $response = $response->withBody(new Stream($stream));

        if ($overwriteMode && !$isIdExist) {
            $response = $response->withStatus(201);
        }

        return $response;
    }
}