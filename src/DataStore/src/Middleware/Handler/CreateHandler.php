<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Middleware\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
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
        $isItemExist = false;
        $row = $request->getParsedBody();
        $primaryKeyValue = $request->getAttribute('primaryKeyValue');
        $overwriteMode = $request->getAttribute('overwriteMode');

        if ($primaryKeyValue) {
            $primaryKeyIdentifier = $this->dataStore->getIdentifier();
            $row = array_merge([$primaryKeyIdentifier => $primaryKeyValue], $row);
            $existingRow = $this->dataStore->read($primaryKeyValue);
            $isItemExist = !empty($existingRow);
        }

        $response = new Response();

        if (!$isItemExist) {
            $response = $response->withStatus(201);
            $location = $request->getUri()->getPath();
            $response = $response->withHeader('Location', $location);
        }

        $newItem = $this->dataStore->create($row, $overwriteMode);
        $response = $response->withBody($this->createStream($newItem));

        return $response;
    }
}