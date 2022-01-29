<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Middleware\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use rollun\datastore\DataStore\Interfaces\ReadInterface;
use rollun\datastore\Rql\Node\AggregateFunctionNode;
use Xiag\Rql\Parser\Node\LimitNode;
use Xiag\Rql\Parser\Node\SelectNode;
use Xiag\Rql\Parser\Query;
use Laminas\Diactoros\Response;

/**
 * Class QueryHandler
 * @package rollun\datastore\Middleware\Handler
 */
class QueryHandler extends AbstractHandler
{
    /**
     * {@inheritdoc}
     */
    public function canHandle(ServerRequestInterface $request): bool
    {
        $canHandle = $request->getMethod() === "GET";
        $query = $request->getAttribute('rqlQueryObject');

        $canHandle = $canHandle && ($query instanceof Query);

        $primaryKeyValue = $request->getAttribute('primaryKeyValue');
        $canHandle = $canHandle && is_null($primaryKeyValue);

        return $canHandle;
    }

    /**
     * {@inheritdoc}
     */
    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Query $rqlQuery */
        $rqlQuery = $request->getAttribute('rqlQueryObject');
        $items = $this->dataStore->query($rqlQuery);

        $response = new Response();
        $response = $response->withBody($this->createStream($items));

        if ($request->getAttribute('withContentRange')) {
            $contentRange = $this->createContentRange($rqlQuery, $items);
            $response = $response->withHeader('Content-Range', $contentRange);
        }

        return $response;
    }

    protected function createContentRange(Query $rqlQuery, $items)
    {
        $limitNode = $rqlQuery->getLimit();
        $total = $this->getTotalItems($rqlQuery);

        if ($limitNode) {
            $offset = $limitNode->getOffset() ?? 0;
        } else {
            $offset = 0;
        }

        return "items " . ($offset + 1) . "-" . ($offset + count($items)) . "/$total";
    }

    /**
     * Get total count items in data store
     *
     * @param Query $rqlQuery
     * @return mixed
     */
    protected function getTotalItems(Query $rqlQuery)
    {
        $rqlQuery->setLimit(new LimitNode(ReadInterface::LIMIT_INFINITY));
        $aggregateCountFunction = new AggregateFunctionNode('count', $this->dataStore->getIdentifier());

        $rqlQuery->setSelect(new SelectNode([$aggregateCountFunction]));
        $aggregateCount = $this->dataStore->query($rqlQuery);

        return current($aggregateCount)["$aggregateCountFunction"];
    }
}
