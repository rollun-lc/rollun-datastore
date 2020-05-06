<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Middleware\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use rollun\datastore\DataStore\DataStoreException;
use Zend\Diactoros\Response;

/**
 * Class CreateHandler
 * @package rollun\datastore\Middleware\Handler
 */
class CreateHandler extends AbstractHandler
{
    /**
     * {@inheritdoc}
     */
    public function canHandle(ServerRequestInterface $request): bool
    {
        $canHandle = $request->getMethod() === "POST";
        $row = $request->getParsedBody();

        $canHandle = $canHandle
            && isset($row)
            && is_array($row)
            && array_reduce(
                array_keys($row),
                function ($carry, $item) {
                    return $carry && is_string($item);
                },
                true
            );

        return $canHandle && $this->isRqlQueryEmpty($request);
    }

    /**
     * {@inheritdoc}
     */
    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        $row = $request->getParsedBody();
        $overwriteMode = $request->getAttribute('overwriteMode');
        $primaryKeyIdentifier = $this->dataStore->getIdentifier();
        $isRowExist = false;

        $primaryKeyValue = isset($row[$primaryKeyIdentifier]) ?
            $row[$primaryKeyIdentifier] : $request->getAttribute('primaryKeyValue');
        if ($primaryKeyValue) {
            $row = array_merge([$primaryKeyIdentifier => $primaryKeyValue], $row);
        }

        $response = new Response();

        if ($primaryKeyValue) {
            $isRowExist = !empty($this->dataStore->read($primaryKeyValue));

            if ($isRowExist && !$overwriteMode) {
                throw new DataStoreException("Item with id '{$primaryKeyValue}' already exist");
            }
        }

        if (!$isRowExist) {
            $response = $response->withStatus(201);
            $location = $request->getUri()
                ->getPath();
            $response = $response->withHeader('Location', $location);
        }

        $newItem = $this->dataStore->create($row, $overwriteMode);
        $response = $response->withBody($this->createStream($newItem));

        return $response;
    }
}
