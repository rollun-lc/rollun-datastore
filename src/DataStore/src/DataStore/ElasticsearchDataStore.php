<?php
declare(strict_types=1);

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore;

use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Conflict409Exception;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use rollun\datastore\DataStore\Elasticsearch\ElasticsearchQueryBuilder;
use Xiag\Rql\Parser\Query;

/**
 * Elasticsearch DataStore implementation.
 *
 * This class provides CRUD operations for Elasticsearch indices.
 * Query building and result processing is delegated to ElasticsearchQueryBuilder.
 *
 * Architecture:
 * - ElasticsearchDataStore: CRUD operations (analogous to DbTable)
 * - ElasticsearchQueryBuilder: Query building (analogous to SqlQueryBuilder)
 * - ElasticsearchAggregationBuilder: Aggregation building
 * - ElasticsearchSortBuilder: Sort building
 * - ElasticsearchResultNormalizer: Result normalization
 * - RqlToElasticsearchDslAdapter: Condition building (analogous to SqlConditionBuilder)
 */
final class ElasticsearchDataStore extends DataStoreAbstract
{
    private const REFRESH_POLICY = 'wait_for';

    private readonly ElasticsearchQueryBuilder $queryBuilder;

    public function __construct(
        private readonly Client $client,
        private readonly string $index,
        private readonly string $identifier = self::DEF_ID,
        private readonly LoggerInterface $logger = new NullLogger()
    ) {
        $this->queryBuilder = new ElasticsearchQueryBuilder(
            $this->client,
            $this->index,
            $this->identifier,
            $this->logger
        );
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function create($itemData, $rewriteIfExist = false)
    {
        $record = $this->normalizeRecordData($itemData);
        $id = $this->extractIdentifierFromRecord($record, true);
        $body = $this->buildDocumentBody($record, $id);

        $params = [
            'index' => $this->index,
            'id' => (string) $id,
            'body' => $body,
            'refresh' => self::REFRESH_POLICY,
        ];

        if (!$rewriteIfExist) {
            $params['op_type'] = 'create';
        }

        try {
            $this->client->index($params);
        } catch (Conflict409Exception $exception) {
            throw new DataStoreException("Item with id '{$id}' already exist", 0, $exception);
        } catch (\Throwable $exception) {
            throw new DataStoreException("Can't insert item with id = {$id}", 0, $exception);
        }

        return $this->read($id);
    }

    public function update($itemData, $createIfAbsent = false)
    {
        $record = $this->normalizeRecordData($itemData);
        $identifier = $this->getIdentifier();

        if (!array_key_exists($identifier, $record)) {
            throw new DataStoreException('Item must has primary key');
        }

        $id = $record[$identifier];
        $this->checkIdentifierType($id);

        $storedRecord = $this->read($id);
        if ($storedRecord === null && !$createIfAbsent) {
            throw new DataStoreException("[{$this->index}]Can't update item with id = {$id}");
        }

        $recordForStore = $storedRecord === null ? $record : array_merge($storedRecord, $record);

        try {
            $this->client->index([
                'index' => $this->index,
                'id' => (string) $id,
                'body' => $this->buildDocumentBody($recordForStore, $id),
                'refresh' => self::REFRESH_POLICY,
            ]);
        } catch (\Throwable $exception) {
            throw new DataStoreException("Can't update item with id = {$id}", 0, $exception);
        }

        return $this->read($id);
    }

    public function delete($id)
    {
        $this->checkIdentifierType($id);
        $record = $this->read($id);

        if ($record === null) {
            return null;
        }

        try {
            $this->client->delete([
                'index' => $this->index,
                'id' => (string) $id,
                'refresh' => self::REFRESH_POLICY,
            ]);
        } catch (Missing404Exception) {
            return null;
        } catch (\Throwable $exception) {
            throw new DataStoreException("Can't delete item with id = {$id}", 0, $exception);
        }

        return $record;
    }

    public function deleteAll()
    {
        try {
            $response = $this->client->deleteByQuery([
                'index' => $this->index,
                'body' => [
                    'query' => [
                        'match_all' => (object) [],
                    ],
                ],
                'refresh' => true,
                'conflicts' => 'proceed',
            ]);
        } catch (Missing404Exception) {
            return 0;
        } catch (\Throwable $exception) {
            throw new DataStoreException("Can't delete all items from index '{$this->index}'", 0, $exception);
        }

        if (!is_array($response)) {
            return 0;
        }

        return (int) ($response['deleted'] ?? 0);
    }

    public function read($id)
    {
        $this->checkIdentifierType($id);

        try {
            $response = $this->client->get([
                'index' => $this->index,
                'id' => (string) $id,
            ]);
        } catch (Missing404Exception) {
            $this->logger->info('ElasticsearchDataStore read: document not found', [
                'index' => $this->index,
                'id' => (string) $id,
            ]);
            return null;
        }

        if (!is_array($response)) {
            return null;
        }

        $record = $response['_source'] ?? null;

        if (!is_array($record)) {
            return null;
        }

        if (!array_key_exists($this->identifier, $record)) {
            $record[$this->identifier] = $id;
        }

        return $record;
    }

    public function has($id)
    {
        return $this->read($id) !== null;
    }

    /**
     * Execute RQL query and return results.
     *
     * Query building is delegated to ElasticsearchQueryBuilder.
     *
     * @param Query $query
     * @return array
     * @throws DataStoreException
     */
    public function query(Query $query)
    {
        return $this->queryBuilder->query($query);
    }

    public function count(): int
    {
        try {
            $response = $this->client->count([
                'index' => $this->index,
                'body' => [
                    'query' => [
                        'match_all' => (object) [],
                    ],
                ],
            ]);
        } catch (Missing404Exception) {
            $this->logger->info('ElasticsearchDataStore count: index not found', [
                'index' => $this->index,
            ]);
            return 0;
        }

        if (!is_array($response)) {
            return 0;
        }

        return (int) ($response['count'] ?? 0);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->query(new Query()));
    }

    protected function checkIdentifierType($id)
    {
        $idType = gettype($id);

        if ($idType === 'integer' || $idType === 'double' || $idType === 'string') {
            return;
        }

        throw new DataStoreException('Type of Identifier is wrong - ' . $idType);
    }

    /**
     * @param mixed $record
     * @return array
     */
    private function normalizeRecordData(mixed $record): array
    {
        if (is_array($record)) {
            return $record;
        }

        if ($record instanceof \ArrayObject) {
            return $record->getArrayCopy();
        }

        if (is_object($record)) {
            $data = get_object_vars($record);
            if (is_array($data)) {
                return $data;
            }
        }

        throw new DataStoreException('Item data must be an array or object with public properties.');
    }

    /**
     * @param array $record
     * @param bool $allowGenerate
     * @return int|float|string
     */
    private function extractIdentifierFromRecord(array &$record, bool $allowGenerate): int|float|string
    {
        $identifier = $this->getIdentifier();
        $id = $record[$identifier] ?? null;

        if ($id === null || $id === '') {
            if (!$allowGenerate) {
                throw new DataStoreException('Item must has primary key');
            }

            $id = $this->generateIdentifier();
            $record[$identifier] = $id;
        }

        $this->checkIdentifierType($id);

        return $id;
    }

    /**
     * @param array $record
     * @param int|float|string $id
     * @return array
     */
    private function buildDocumentBody(array $record, int|float|string $id): array
    {
        if ($this->identifier === '_id') {
            unset($record['_id']);
            return $record;
        }

        $record[$this->identifier] = $id;

        return $record;
    }

    /**
     * Generate unique identifier for new record.
     *
     * @return string
     */
    private function generateIdentifier(): string
    {
        return bin2hex(random_bytes(16));
    }
}
