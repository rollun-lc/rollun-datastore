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
        // Только PATCH
        if ($request->getMethod() !== "PATCH") {
            return false;
        }

        // Нет id в path
        if ($request->getAttribute('primaryKeyValue')) {
            return false;
        }

        // Есть rqlQueryObject с фильтром
        $query = $request->getAttribute('rqlQueryObject');
        if (!($query instanceof Query) || is_null($query->getQuery())) {
            return false;
        }

        // Тело — ассоциативный массив (поля для обновления)
        $fields = $request->getParsedBody();
        if (
            !isset($fields) ||
            !is_array($fields) ||
            array_keys($fields) === range(0, count($fields) - 1) // Это list, а не assoc array
        ) {
            return false;
        }

        // Только фильтр (нет limit/sort/select)
        return $this->isRqlQueryEmptyExceptFilter($query);
    }

    /**
     * @inheritDoc
     */
    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getAttribute('rqlQueryObject');
        $fields = $request->getParsedBody();

        try {
            $result = $this->dataStore->queriedUpdate($fields, $query);
        } catch (DataStoreException) {
//            Ignore results as in multiCreate
        }

        $response = new Response();
        $response = $response->withBody($this->createStream($result));
        return $response;
    }

    /**
     * Проверка что только RQL-фильтр, нет limit/sort/select
     */
    private function isRqlQueryEmptyExceptFilter(Query $query): bool
    {
        return is_null($query->getLimit())
            && is_null($query->getSort())
            && is_null($query->getSelect());
    }
}
