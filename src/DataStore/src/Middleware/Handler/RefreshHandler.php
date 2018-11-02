<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Middleware\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use rollun\datastore\DataStore\Interfaces\RefreshableInterface;
use rollun\rest\RestException;
use Zend\Diactoros\Response;

/**
 * Class RefreshHandler
 * @package rollun\datastore\Middleware\Handler
 */
class RefreshHandler extends AbstractHandler
{
    /**
     * {@inheritdoc}
     */
    protected function canHandle(ServerRequestInterface $request): bool
    {
        return $request->getMethod() === "PATCH";
    }

    /**
     * {@inheritdoc}
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
