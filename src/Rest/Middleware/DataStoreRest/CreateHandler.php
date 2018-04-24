<?php


namespace rollun\rest\Middleware\DataStoreRest;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;

class CreateHandler extends AbstractHandler
{
    /**
     * check if datastore rest middleware may handle this request
     * @param ServerRequestInterface $request
     * @return bool
     */
    protected function canHandle(ServerRequestInterface $request): bool
    {
        $canHandle = $request->getMethod() === "POST";
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
        $isIdExist = false;
        $row = $request->getParsedBody();
        $primaryKeyValue = $request->getAttribute('primaryKeyValue');
        $overwriteMode = $request->getAttribute('overwriteMode');
        if($primaryKeyValue) {
            $primaryKeyIdentifier = $this->dataStore->getIdentifier();
            $row = array_merge(array($primaryKeyIdentifier => $primaryKeyValue), $row);
            $existingRow = $this->dataStore->read($primaryKeyValue);

            $isIdExist = !empty($existingRow);
        }

        $response = new Response();
        if (!$isIdExist) {
            $response = $response->withStatus(201);
            $location = $request->getUri()->getPath();
            $response = $response->withHeader('Location', $location);
        }
        $newItem = $this->dataStore->create($row, $overwriteMode);
        $stream = fopen("data://text/plain;base64,".base64_encode(serialize($newItem)), 'r');
        $response = $response->withBody(new Stream($stream));
        return $response;
    }
}