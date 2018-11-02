<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Middleware\Handler;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Xiag\Rql\Parser\Query;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;

/**
 * Class ReadHandler
 * @package rollun\datastore\Middleware\Handler
 */
class ReadHandler extends AbstractHandler
{
    /**
     * {@inheritdoc}
     */
    protected function canHandle(ServerRequestInterface $request): bool
    {
        $canHandle = $request->getMethod() === "GET";

        $primaryKeyValue = $request->getAttribute('primaryKeyValue');
        $canHandle = $canHandle && isset($primaryKeyValue);

        $query = $request->getAttribute('rqlQueryObject');

        if (isset($query) && !($query instanceof Query)) {
            throw new InvalidArgumentException(
                'Expected ' . Query::class . ', ' . gettype($query) . ' given'
            );
        }

        $canHandle = $canHandle
            && (is_null($query)
                || (is_null($query->getLimit())
                    && is_null($query->getSort())
                    && is_null($query->getSelect())
                    && is_null($query->getQuery())));


        return $canHandle;
    }

    /**
     * {@inheritdoc}
     */
    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        $primaryKeyValue = $request->getAttribute('primaryKeyValue');
        $items = $this->dataStore->read($primaryKeyValue);

        $response = new Response();

        $stream = fopen("data://text/plain;base64," . base64_encode(serialize($items)), 'r');
        $response = $response->withBody(new Stream($stream));

        return $response;
    }
}
