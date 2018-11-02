<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Middleware\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;

/**
 * Class CreateHandler
 * @package rollun\datastore\Middleware\Handler
 */
class CreateHandler extends AbstractHandler
{
    /**
     * {@inheritdoc}
     */
    protected function canHandle(ServerRequestInterface $request): bool
    {
        $canHandle = $request->getMethod() === "POST";
        $row = $request->getParsedBody();

        $canHandle = $canHandle
            && isset($row)
            && is_array($row)
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
        $isIdExist = false;
        $row = $request->getParsedBody();
        $primaryKeyValue = $request->getAttribute('primaryKeyValue');
        $overwriteMode = $request->getAttribute('overwriteMode');

        if ($primaryKeyValue) {
            $primaryKeyIdentifier = $this->dataStore->getIdentifier();
            $row = array_merge([$primaryKeyIdentifier => $primaryKeyValue], $row);
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
        $stream = fopen("data://text/plain;base64," . base64_encode(serialize($newItem)), 'r');
        $response = $response->withBody(new Stream($stream));

        return $response;
    }
}