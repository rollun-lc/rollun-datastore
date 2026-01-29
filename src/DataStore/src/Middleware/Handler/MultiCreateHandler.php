<?php

declare(strict_types=1);

namespace rollun\datastore\Middleware\Handler;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use rollun\datastore\DataStore\DataStoreException;

/**
 * Class MultiCreateHandler
 *
 * @author    Roman Ratsun <r.ratsun.rollun@gmail.com>
 *
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license   LICENSE.md New BSD License
 */
class MultiCreateHandler extends AbstractHandler
{
    private const MULTI_POLICY_ENV = 'DATASTORE_MULTI_POLICY';
    private const MULTI_POLICY_SOFT = 'soft';

    /**
     * @inheritDoc
     */
    public function canHandle(ServerRequestInterface $request): bool
    {
        // exit if wrong request method
        if ($request->getMethod() !== "POST") {
            return false;
        }

        //        // exit if IsMultiCreate disabled
        //        if (!in_array($request->getHeaderLine('IsMultiCreate'), ['true', '1'])) {
        //            return false;
        //        }

        // get rows
        $rows = $request->getParsedBody();

        // exit if wrong body
        if (!isset($rows) || !is_array($rows) || !isset($rows[0]) || !is_array($rows[0])) {
            return false;
        }

        foreach ($rows as $row) {
            $canHandle = isset($row)
                && is_array($row)
                && array_reduce(
                    array_keys($row),
                    fn($carry, $item) => $carry && is_string($item),
                    true
                );

            // exit if wrong body row
            if (!$canHandle) {
                return false;
            }
        }

        return $this->isRqlQueryEmpty($request);
    }

    /**
     * @inheritDoc
     */
    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        // get rows
        $rows = $request->getParsedBody();

        if (method_exists($this->dataStore, 'multiCreate')) {
            $result = $this->dataStore->multiCreate($rows);
        } elseif ($this->isSoftMultiPolicy()) {
            $result = $this->multiCreateFallback($rows);
        } else {
            throw new DataStoreException(
                'Multi create is not supported by this datastore. ' .
                'Please implement the multiCreate() method or use individual create() calls.'
            );
        }

        return new JsonResponse(
            $result,
            201,
            [
                'Location' => $request->getUri()->getPath(),
            ]
        );
    }

    private function multiCreateFallback(array $records): array
    {
        $ids = [];
        $identifier = $this->dataStore->getIdentifier();

        foreach ($records as $record) {
            try {
                $createdRecord = $this->dataStore->create($record);
                $ids[] = $createdRecord[$identifier];
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
