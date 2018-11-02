<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Middleware\Handler;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use rollun\datastore\DataStore\Interfaces\ReadInterface;
use rollun\datastore\Rql\Node\AggregateFunctionNode;
use Xiag\Rql\Parser\Node\LimitNode;
use Xiag\Rql\Parser\Node\SelectNode;
use Xiag\Rql\Parser\Query;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;

/**
 * Class QueryHandler
 * @package rollun\datastore\Middleware\Handler
 */
class QueryHandler extends AbstractHandler
{
    /**
     * {@inheritdoc}
     */
    protected function canHandle(ServerRequestInterface $request): bool
    {
        $canHandle = $request->getMethod() === "GET";
        $query = $request->getAttribute('rqlQueryObject');

        if (isset($query) && !($query instanceof Query)) {
            throw new InvalidArgumentException(
                'Expected ' . Query::class . ', ' . gettype($query) . ' given'
            );
        }

        $primaryKeyValue = $request->getAttribute('primaryKeyValue');
        $canHandle = $canHandle && is_null($primaryKeyValue);

        return $canHandle;
    }

    /**
     * {@inheritdoc}
     */
    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Query $rqlQueryObject */
        $rqlQueryObject = $request->getAttribute('rqlQueryObject');
        $rqlLimitNode = $rqlQueryObject->getLimit();

        $rowSet = $this->dataStore->query($rqlQueryObject);

        if ($rqlLimitNode) {
            $rqlQueryObject->setLimit(new LimitNode(ReadInterface::LIMIT_INFINITY));
            $aggregateCountFunction = new AggregateFunctionNode('count', $this->dataStore->getIdentifier());

            $rqlQueryObject->setSelect(new SelectNode([$aggregateCountFunction]));
            $aggregateCount = $this->dataStore->query($rqlQueryObject);

            $count = current($aggregateCount)["$aggregateCountFunction"];

            if (is_null($rqlLimitNode->getOffset())) {
                $offset = '0';
            } else {
                $offset = $rqlLimitNode->getOffset();
            }

            if ($rqlLimitNode->getLimit() == ReadInterface::LIMIT_INFINITY) {
                $limit = $rqlLimitNode->getLimit();
            } else {
                $limit = $count;
            }

            $contentRange = "items $offset-$limit/$count";
        } else {
            $count = count($rowSet);
            $contentRange = "items 0-$count/$count";
        }

        $response = new Response();

        $stream = fopen("data://text/plain;base64," . base64_encode(serialize($rowSet)), 'r');
        $response = $response->withBody(new Stream($stream));

        $response = $response->withHeader('Content-Range', $contentRange);

        return $response;
    }
}
