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
            // ['column1' => 'val1', 'column2' => 'val2']
            empty($fields)
        ) {
            return false;
        }

        return $this->isRqlQueryNotContainsGroupByAndSelect($query);
    }

    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getAttribute('rqlQueryObject');
        $fields = $request->getParsedBody();

        if ($this->dataStore instanceof DataStoreInterface) {
            $result = $this->dataStore->queriedUpdate($fields, $query);
        } else {
            $identifier = $this->dataStore->getIdentifier();

            $items   = $this->dataStore->query($query);
            $updated = [];

            foreach ($items as $item) {
                $payload = $fields;
                $payload[$identifier] = $item[$identifier];

                try {
                    $updated[] = $this->dataStore->update($payload);
                    usleep(10000); // 10ms
                } catch (DataStoreException) {
                    //Ignore result...
                }
            }

            $result = array_column($updated, $identifier);
        }

        $response = new Response();
        $response = $response->withBody($this->createStream($result));
        return $response;
    }

    /**
     * Check that rqs is only RQL-filter, no groupBy/select
     */
    private function isRqlQueryNotContainsGroupByAndSelect(Query $query): bool
    {
        return is_null($query->getGroupBy())
            && is_null($query->getSelect());
    }
}
