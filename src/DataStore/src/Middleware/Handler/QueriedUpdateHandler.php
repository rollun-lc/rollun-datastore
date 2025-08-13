<?php

namespace rollun\datastore\Middleware\Handler;

use Laminas\Diactoros\Response;
use Psr\Http\Message\ServerRequestInterface;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\Interfaces\DataStoreInterface;
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

        if ($query->getLimit() === null) {
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

        return $this->isRqlQueryEmptyExceptFilterAndLimit($query);

        // TODO: добавить проверку что датастор может выполнить queriedUpdate()
    }

    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getAttribute('rqlQueryObject');
        $fields = $request->getParsedBody();

        if ($this->dataStore instanceof DataStoreInterface) {
            $result = $this->dataStore->queriedUpdate($fields, $query);
        } else {
            // TODO: implemets queriedUpdate method
            throw new DataStoreException('Data store object is not supporting queried update temporarily');
        }

        $response = new Response();
        $response = $response->withBody($this->createStream($result));
        return $response;
    }

    /**
     * Check that rqs is only RQL-filter, no groupBy/select
     */
    private function isRqlQueryEmptyExceptFilterAndLimit(Query $query): bool
    {
        return is_null($query->getGroupBy())
            && is_null($query->getSelect());
    }
}
