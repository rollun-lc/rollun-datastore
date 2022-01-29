<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Middleware\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use rollun\datastore\Middleware\DataStoreAbstract;
use rollun\datastore\Middleware\JsonRenderer;
use Xiag\Rql\Parser\Query;
use Laminas\Diactoros\Stream;

abstract class AbstractHandler extends DataStoreAbstract
{
    /**
     * Check if datastore rest middleware may handle this request
     *
     * @param ServerRequestInterface $request
     * @return bool
     */
    abstract protected function canHandle(ServerRequestInterface $request): bool;

    /**
     * Handle request to dataStore
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    abstract protected function handle(ServerRequestInterface $request): ResponseInterface;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->canHandle($request)) {
            $response = $this->handle($request);
            $stream = $response->getBody();

            if (isset($stream)) {
                $data = unserialize($stream->getContents());
                $request = $request->withAttribute(JsonRenderer::RESPONSE_DATA, $data);
            }

            $request = $request->withAttribute(ResponseInterface::class, $response);
        }

        $response = $handler->handle($request);

        return $response;
    }

    /**
     * Create stream with base64 encoded data
     *
     * @param $data
     * @return Stream
     */
    protected function createStream($data)
    {
        $stream = fopen("data://text/plain;base64," . base64_encode(serialize($data)), 'r');

        return new Stream($stream);
    }

    /**
     * @param ServerRequestInterface $request
     * @return bool
     */
    protected function isRqlQueryEmpty($request): bool
    {
        $query = $request->getAttribute('rqlQueryObject');

        if (!($query instanceof Query)) {
            return true;
        }

        return is_null($query->getLimit())
            && is_null($query->getSort())
            && is_null($query->getSelect())
            && is_null($query->getQuery());
    }
}
