<?php

declare(strict_types=1);

namespace rollun\datastore\Middleware\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use rollun\datastore\DataStore\DataStoreException;
use Zend\Diactoros\Response\JsonResponse;

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

        // Must be non-empty array
        if (!isset($rows) || !is_array($rows) || empty($rows)) {
            return false;
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
                function ($carry, $item) {
                    return $carry && is_string($item);
                },
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

        // Strict approach: require multiUpdate to be implemented
        if (!method_exists($this->dataStore, 'multiUpdate')) {
            throw new DataStoreException(
                'Multi update is not supported by this datastore. ' .
                'Please implement the multiUpdate() method or use individual update() calls.'
            );
        }

        $result = $this->dataStore->multiUpdate($rows);

        return new JsonResponse($result);
    }
}
