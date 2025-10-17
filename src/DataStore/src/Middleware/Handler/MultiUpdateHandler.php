<?php

declare(strict_types=1);

namespace rollun\datastore\Middleware\Handler;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\Interfaces\DataStoreInterface;

/**
 * Handler for multiUpdate operation
 *
 * Handles PUT request with array of records to update multiple records at once
 */
class MultiUpdateHandler extends AbstractHandler
{
    /**
     * {@inheritdoc}
     */
    public function canHandle(ServerRequestInterface $request): bool
    {
        // Must be PUT method
        if ($request->getMethod() !== "PUT") {
            return false;
        }

        // Must not have primaryKeyValue (that would be single update)
        if ($request->getAttribute('primaryKeyValue')) {
            return false;
        }

        // Must have empty RQL query
        if (!$this->isRqlQueryEmpty($request)) {
            return false;
        }

        $rows = $request->getParsedBody();

        // Empty array is valid (returns empty result)
        if (!isset($rows) || !is_array($rows)) {
            return false;
        }

        // Empty array is allowed
        if (empty($rows)) {
            return true;
        }

        // First element must be array (array of records)
        if (!isset($rows[0])) {
            return false;
        }

        // If not array of arrays, not valid
        if (!is_array($rows[0])) {
            return false;
        }

        // Validate all elements are associative arrays
        foreach ($rows as $row) {
            if (!is_array($row)) {
                return false;
            }

            // Check if it's associative array (not list)
            $isAssociative = array_reduce(
                array_keys($row),
                fn($carry, $item) => $carry && is_string($item),
                true
            );

            if (!$isAssociative) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        $rows = $request->getParsedBody();

        if ($this->dataStore instanceof DataStoreInterface) {
            $result = $this->dataStore->multiUpdate($rows);
        } else {
            $result = [];
            foreach ($rows as $row) {
                try {
                    $updatedRecord = $this->dataStore->update($row);
                    $result[] = $updatedRecord[$this->dataStore->getIdentifier()];
                    usleep(10000); // 10ms
                } catch (DataStoreException) {
                    // Ignore failed updates
                }
            }
        }

        return new JsonResponse($result);
    }
}

