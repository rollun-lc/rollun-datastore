<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\rest\Middleware\DataStoreRest;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;

class UpdateHandler extends AbstractHandler
{
    /**
     * {@inheritdoc}
     */
    protected function canHandle(ServerRequestInterface $request): bool
    {
        $canHandle = $request->getMethod() === "PUT";

        $primaryKeyValue = $request->getAttribute('primaryKeyValue');
        $canHandle = $canHandle && isset($primaryKeyValue);

        $row = $request->getParsedBody();
        $canHandle = $canHandle && isset($row) && is_array($row)
            && array_reduce(
                array_keys($row),
                function ($carry, $item) {
                    return $carry && !is_integer($item);
                },
                true
            );

        return $canHandle;
    }

    /**
     * {@inheritdoc}
     */
    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        $primaryKeyValue = $request->getAttribute('primaryKeyValue');
        $primaryKeyIdentifier = $this->dataStore->getIdentifier();
        $item = $request->getParsedBody();

        $item = array_merge([$primaryKeyIdentifier => $primaryKeyValue], $item);
        $overwriteMode = $request->getAttribute('overwriteMode');
        $isIdExist = !empty($this->dataStore->read($primaryKeyValue));

        $response = new Response();
        $newItem = $this->dataStore->update($item, $overwriteMode);

        $stream = fopen("data://text/plain;base64," . base64_encode(serialize($newItem)), 'r');
        $response = $response->withBody(new Stream($stream));

        if ($overwriteMode && !$isIdExist) {
            $response = $response->withStatus(201);
        }

        return $response;
    }
}