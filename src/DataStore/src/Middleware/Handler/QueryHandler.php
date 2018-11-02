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
use Zend\Diactoros\Response;

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
        $limitNode = $rqlQuery->getLimit();

        $rowSet = $this->dataStore->query($rqlQuery);

        if ($limitNode) {
            $rqlQuery->setLimit(new LimitNode(ReadInterface::LIMIT_INFINITY));
            $aggregateCountFunction = new AggregateFunctionNode('count', $this->dataStore->getIdentifier());

            $rqlQuery->setSelect(new SelectNode([$aggregateCountFunction]));
            $aggregateCount = $this->dataStore->query($rqlQuery);

            $count = current($aggregateCount)["$aggregateCountFunction"];

            if (is_null($limitNode->getOffset())) {
                $offset = '0';
            } else {
                $offset = $limitNode->getOffset();
            }

            if ($limitNode->getLimit() == ReadInterface::LIMIT_INFINITY) {
                $limit = $limitNode->getLimit();
            } else {
                $limit = $count;
            }

            $contentRange = "items $offset-" . ($offset + $limit) . "/$count";
        } else {
            $count = count($rowSet);
            $contentRange = "items 0-$count/$count";
        }

        $response = new Response();
        $response = $response->withBody($this->createStream($rowSet));
        $response = $response->withHeader('Content-Range', $contentRange);

        return $response;
    }
}
