<?php

namespace rollun\datastore\Middleware\Handler;

use Laminas\Diactoros\Response;
use Psr\Http\Message\ServerRequestInterface;
use rollun\datastore\DataStore\DataStoreException;
use Xiag\Rql\Parser\Query;
use Psr\Http\Message\ResponseInterface;

class QueriedUpdateHandler extends AbstractHandler
{
    protected function canHandle(ServerRequestInterface $request): bool
    {
        if ($request->getMethod() !== 'PATCH') {
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
        // Тело — ассоциативный массив
        $body = $request->getParsedBody();
        if (!is_array($body) || array_reduce(array_keys($body),
                fn($c, $k) => $c && !is_int($k), true) === false) {
            return false;
        }
        // Без limit/sort/select — только фильтр
        return $this->isRqlQueryEmptyExceptFilter($request);
    }

    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getAttribute('rqlQueryObject');
        $fields = $request->getParsedBody();

        try {
            $ids = $this->dataStore->queriedUpdate($fields, $query);
        } catch (DataStoreException $e) {
            return (new Response())
                ->withStatus(400)
                ->withBody($this->createStream(['error' => $e->getMessage()]));
        }

        return (new Response())
            ->withBody($this->createStream($ids));
    }

    private function isRqlQueryEmptyExceptFilter(ServerRequestInterface $request): bool
    {
        $query = $request->getAttribute('rqlQueryObject');
        return is_null($query->getLimit())
            && is_null($query->getSort())
            && is_null($query->getSelect())
            && !is_null($query->getQuery());
    }
}