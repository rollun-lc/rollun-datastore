<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Middleware\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response;

/**
 * Class ReadHandler
 * @package rollun\datastore\Middleware\Handler
 */
class ReadHandler extends AbstractHandler
{
    /**
     * {@inheritdoc}
     */
    public function canHandle(ServerRequestInterface $request): bool
    {
        $canHandle = $request->getMethod() === "GET";

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
        $items = $this->dataStore->read($primaryKeyValue);

        $response = new Response();
        $response = $response->withBody($this->createStream($items));

        return $response;
    }
}
