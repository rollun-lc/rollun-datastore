<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Middleware\Handler;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class DeleteHandler
 * @package rollun\datastore\Middleware\Handler
 */
class DeleteHandler extends AbstractHandler
{
    /**
     * {@inheritdoc}
     */
    public function canHandle(ServerRequestInterface $request): bool
    {
        $canHandle = $request->getMethod() === "DELETE";

        $primaryKeyValue = $request->getAttribute('primaryKeyValue');
        $canHandle = $canHandle && isset($primaryKeyValue);

        return $canHandle && $this->isRqlQueryEmpty($request);
    }

    /**
     * {@inheritdoc}
     */
    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        $primaryKeyValue = $request->getAttribute('primaryKeyValue');
        $items = $this->dataStore->delete($primaryKeyValue);

        $response = new JsonResponse($items);

        if (!isset($items)) {
            $response = $response->withStatus(204);
        }

        return $response;
    }
}
