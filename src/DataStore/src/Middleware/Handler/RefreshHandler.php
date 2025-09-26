<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Middleware\Handler;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use rollun\datastore\DataStore\Interfaces\RefreshableInterface;
use rollun\datastore\Middleware\RestException;

/**
 * Class RefreshHandler
 * @package rollun\datastore\Middleware\Handler
 */
class RefreshHandler extends AbstractHandler
{
    /**
     * {@inheritdoc}
     */
    public function canHandle(ServerRequestInterface $request): bool
    {
        return $request->getMethod() === "PATCH" && $this->isRqlQueryEmpty($request);
    }

    /**
     * {@inheritdoc}
     */
    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->dataStore instanceof RefreshableInterface) {
            $this->dataStore->refresh();

            return new JsonResponse([]);
        }

        throw new RestException("DataStore is not implement RefreshableInterface");
    }
}
