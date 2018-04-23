<?php


namespace rollun\datastore\Middleware\DataStoreRest;


use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use rollun\actionrender\Renderer\AbstractRenderer;
use rollun\datastore\DataStore\Interfaces\ReadInterface;
use rollun\datastore\Rql\Node\AggregateFunctionNode;
use Xiag\Rql\Parser\Node\LimitNode;
use Xiag\Rql\Parser\Node\SelectNode;
use Xiag\Rql\Parser\Query;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;

class QueryHandler extends AbstractHandler
{
    /**
     * check if datastore rest middleware may handle this request
     * @param ServerRequestInterface $request
     * @return bool
     */
    protected function canHandle(ServerRequestInterface $request): bool
    {
        $canHandle = $request->getMethod() === "GET";
        /** @var Query $query */
        $query = $request->getAttribute('rqlQueryObject');
        $primaryKeyValue = $request->getAttribute('primaryKeyValue');

        $canHandle = $canHandle && isset($query) && $query instanceof Query && is_null($primaryKeyValue);
        return $canHandle;
    }

    /**
     * Handle request to dataStore;
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Query $rqlQueryObject */
        $rqlQueryObject = $request->getAttribute('rqlQueryObject');

        $rqlLimitNode = $rqlQueryObject->getLimit();

        $rowset = $this->dataStore->query($rqlQueryObject);

        if ($rqlLimitNode) {
            $rqlQueryObject->setLimit(new LimitNode(ReadInterface::LIMIT_INFINITY));
            $aggregateCountFunc = new AggregateFunctionNode('count', $this->dataStore->getIdentifier());
            $rqlQueryObject->setSelect(new SelectNode([$aggregateCountFunc]));
            $aggregateCount = $this->dataStore->query($rqlQueryObject);
            $count = current($aggregateCount)["$aggregateCountFunc"];
            $offset = !is_null($rqlLimitNode->getOffset()) ? $rqlLimitNode->getOffset() : '0';
            $limit = !is_null($rqlLimitNode->getLimit()) ?
                ($rqlLimitNode->getLimit() == ReadInterface::LIMIT_INFINITY ? $count : $rqlLimitNode->getLimit()) :
                $count;
            $contentRange = "items $offset-$limit/$count";
        } else {
            $count = count($rowset);
            $contentRange = "items 0-$count/$count";
        }

        $response = new Response();

        $stream = fopen("data://text/plain;base64,".base64_encode(serialize($rowset)), 'r');
        $response = $response->withBody(new Stream($stream));

        $response = $response->withHeader('Content-Range', $contentRange);
        return $response;
    }
}