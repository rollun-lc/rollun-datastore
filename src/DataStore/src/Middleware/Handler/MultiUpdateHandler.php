<?php

declare(strict_types=1);

namespace rollun\datastore\Middleware\Handler;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use rollun\datastore\DataStore\DataStoreException;

/**
 * Handler for multiUpdate operation
 *
 * Handles PUT request with array of records to update multiple records at once
 */
class MultiUpdateHandler extends AbstractHandler
{
    private const MULTI_POLICY_ENV = 'DATASTORE_MULTI_POLICY';
    private const MULTI_POLICY_SOFT = 'soft';

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
        if (!is_array($rows) || $rows === []) {
            return false;
        }

        // Must be a list (sequential numeric keys starting from 0)
        if (!$this->isList($rows)) {
            return false;
        }

        // Validate all elements are non-empty associative arrays
        foreach ($rows as $row) {
            if (!$this->isNonEmptyAssociativeArray($row)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if array is a list (sequential numeric keys starting from 0)
     * Polyfill for array_is_list() which is available in PHP 8.1+
     */
    private function isList(array $array): bool
    {
        return $array === [] || array_keys($array) === range(0, count($array) - 1);
    }

    /**
     * Check if value is a non-empty associative array (not a list)
     */
    private function isNonEmptyAssociativeArray(mixed $value): bool
    {
        if (!is_array($value) || $value === []) {
            return false;
        }

        return !$this->isList($value);
    }

    /**
     * {@inheritdoc}
     */
    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        $rows = $request->getParsedBody();

        if (method_exists($this->dataStore, 'multiUpdate')) {
            $result = $this->dataStore->multiUpdate($rows);
        } elseif ($this->isSoftMultiPolicy()) {
            $result = $this->multiUpdateFallback($rows);
        } else {
            throw new DataStoreException(
                'Multi update is not supported by this datastore. ' .
                'Please implement the multiUpdate() method or use individual update() calls.'
            );
        }

        return new JsonResponse($result);
    }

    private function multiUpdateFallback(array $records): array
    {
        $ids = [];
        $identifier = $this->dataStore->getIdentifier();

        foreach ($records as $record) {
            try {
                $updatedRecord = $this->dataStore->update($record);
                $ids[] = $updatedRecord[$identifier];
            } catch (\Throwable) {
                continue;
            }
        }

        return $ids;
    }

    private function isSoftMultiPolicy(): bool
    {
        $policy = strtolower(trim((string) getenv(self::MULTI_POLICY_ENV)));

        return $policy === self::MULTI_POLICY_SOFT;
    }
}
