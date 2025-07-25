<?php

namespace rollun\datastore\Middleware\Handler;

use Laminas\Diactoros\Response;
use Psr\Http\Message\ServerRequestInterface;
use rollun\datastore\DataStore\DataStoreException;
use Xiag\Rql\Parser\Query;
use Psr\Http\Message\ResponseInterface;

class QueriedUpdateHandler extends AbstractHandler
{
    /**
     * @inheritDoc
     */
    public function canHandle(ServerRequestInterface $request): bool
    {
        if ($request->getMethod() !== "PATCH") {
            return false;
        }

        if ($request->getAttribute('primaryKeyValue')) {
            return false;
        }

        $query = $request->getAttribute('rqlQueryObject');
        if (!($query instanceof Query) || is_null($query->getQuery())) {
            return false;
        }

        $fields = $request->getParsedBody();
        if (
            !isset($fields) ||
            !is_array($fields) ||
            array_keys($fields) === range(0, count($fields) - 1) || // Array is list ['val1', 'val2'] instead of
//            ['column1' => 'val1', 'column2' => 'val2']
            empty($fields)
        ) {
            return false;
        }

        return $this->isRqlQueryEmptyExceptFilter($query);
    }

    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getAttribute('rqlQueryObject');
        $fields = $request->getParsedBody();

        $result = $this->dataStore->queriedUpdate($fields, $query);

        $response = new Response();
        $response = $response->withBody($this->createStream($result));
        return $response;
    }

    /**
     * Check that rqs is only RQL-filter, no limit/sort/select
     */
    private function isRqlQueryEmptyExceptFilter(Query $query): bool
    {
        return is_null($query->getLimit())
            && is_null($query->getSort())
            && is_null($query->getSelect());
    }
}
