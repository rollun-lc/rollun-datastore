<?php

declare(strict_types=1);

namespace rollun\datastore\Middleware\Handler;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\Interfaces\DataStoreInterface;

class MultiUpdateHandler extends AbstractHandler
{
    /**
     * @inheritDoc
     */
    public function canHandle(ServerRequestInterface $request): bool
    {
        if (strtoupper($request->getMethod()) !== 'PATCH') {
            return false;
        }

        $operation = strtolower($request->getHeaderLine('X-DataStore-Operation'));
        //        if ($operation !== 'multi-update') {
        //            return false;
        //        }

        if ($request->getAttribute('primaryKeyValue')) {
            return false;
        }

        if (!$this->isRqlQueryEmpty($request)) {
            return false;
        }

        $rows = $request->getParsedBody();
        if (!is_array($rows) || $rows === []) {
            return false;
        }

        $identifier = $this->dataStore->getIdentifier();
        foreach ($rows as $row) {
            $isAssociative = is_array($row)
                && array_reduce(
                    array_keys($row),
                    static fn($carry, $item) => $carry && is_string($item),
                    true
                );

            if (!$isAssociative || !array_key_exists($identifier, $row)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        $rows = $request->getParsedBody();

        if ($this->dataStore instanceof DataStoreInterface) {
            $result = $this->dataStore->multiUpdate($rows);
        } else {
            throw new DataStoreException("DataStore must implement multiUpdate operation for this request");
            $identifier = $this->dataStore->getIdentifier();
            $updated = [];

            foreach ($rows as $row) {
                try {
                    $updated[] = $this->dataStore->update($row);
                } catch (DataStoreException) {
                    continue;
                }
            }

            $result = array_column($updated, $identifier);
        }

        return (new JsonResponse($result))
            ->withHeader('X-DataStore-Operation', 'multi-update');
    }
}
