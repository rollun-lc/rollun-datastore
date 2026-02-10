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
use rollun\datastore\DataStore\Elasticsearch\RqlToElasticsearchDslAdapter;
use rollun\datastore\Rql\Node\AggregateFunctionNode;
use rollun\datastore\Rql\RqlQuery;
use Xiag\Rql\Parser\Node\LimitNode;
use Xiag\Rql\Parser\Node\SelectNode;
use Xiag\Rql\Parser\Node\SortNode;
use Xiag\Rql\Parser\Query;

class ElasticsearchDataStore extends DataStoreAbstract
{
    private const SEARCH_BATCH_SIZE = 500;
    private const DEFAULT_QUERY_LIMIT = 10000;
    private const REFRESH_POLICY = 'wait_for';
    private readonly RqlToElasticsearchDslAdapter $rqlToElasticsearchDslAdapter;

    public function __construct(
        private readonly Client $client,
        private readonly string $index,
        private readonly string $identifier = self::DEF_ID,
        private readonly LoggerInterface $logger = new NullLogger()
    ) {
        $this->rqlToElasticsearchDslAdapter = new RqlToElasticsearchDslAdapter(
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

    public function query(Query $query)
    {
        if ($this->shouldUseNativeAggregations($query)) {
            return $this->queryWithNativeAggregations($query);
        }

        if ($this->shouldProcessSelectInMemory($query)) {
            return $this->queryWithInMemorySelect($query);
        }

        $limitNode = $query->getLimit();
        $limit = $this->resolveLimit($limitNode);
        $offset = $limitNode ? max(0, (int) $limitNode->getOffset()) : 0;

        if ($limit < 0) {
            throw new DataStoreException('Query limit must be greater or equal to zero.');
        }

        if ($limit === 0) {
            return [];
        }

        $selectFields = $this->extractSelectFields($query->getSelect());
        $sort = $this->appendSortTieBreaker(
            $this->buildSort($query->getSort())
        );

        $baseBody = [
            'query' => $this->rqlToElasticsearchDslAdapter->convert($query->getQuery()),
            'sort' => $sort,
        ];

        if ($selectFields !== []) {
            $baseBody['_source'] = $selectFields;
        }

        $result = [];
        $skipped = 0;
        $remaining = $limit === self::LIMIT_INFINITY ? null : $limit;
        $searchAfter = null;

        while (true) {
            $batchSize = $this->calculateBatchSize($remaining, $offset, $skipped);

            if ($batchSize === 0) {
                break;
            }

            $body = $baseBody;
            $body['size'] = $batchSize;

            if ($searchAfter !== null) {
                $body['search_after'] = $searchAfter;
            }

            try {
                $response = $this->client->search([
                    'index' => $this->index,
                    'body' => $body,
                ]);
            } catch (Missing404Exception) {
                $this->logger->info('ElasticsearchDataStore query: index not found', [
                    'index' => $this->index,
                ]);
                return [];
            }

            if (!is_array($response)) {
                break;
            }

            $hits = $response['hits']['hits'] ?? null;

            if (!is_array($hits) || $hits === []) {
                break;
            }

            foreach ($hits as $hit) {
                if (!is_array($hit)) {
                    continue;
                }

                if ($skipped < $offset) {
                    $skipped++;
                    continue;
                }

                $result[] = $this->normalizeSearchHit($hit, $selectFields);

                if ($remaining !== null) {
                    $remaining--;
                    if ($remaining === 0) {
                        break 2;
                    }
                }
            }

            $lastHit = end($hits);
            $lastSort = is_array($lastHit) ? ($lastHit['sort'] ?? null) : null;

            if (!is_array($lastSort)) {
                break;
            }

            $searchAfter = $lastSort;
        }

        return $result;
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
     * @param SelectNode|null $selectNode
     * @return string[]
     */
    private function extractSelectFields(?SelectNode $selectNode): array
    {
        if ($selectNode === null) {
            return [];
        }

        $fields = [];

        foreach ($selectNode->getFields() as $key => $value) {
            $field = is_int($key) ? $value : $key;

            if (!is_string($field) || $field === '') {
                continue;
            }

            $fields[] = $field;
        }

        return array_values(array_unique($fields));
    }

    /**
     * @param SortNode|null $sortNode
     * @return array[]
     */
    private function buildSort(?SortNode $sortNode): array
    {
        if ($sortNode === null) {
            return [];
        }

        $sort = [];

        foreach ($sortNode->getFields() as $field => $direction) {
            if (!is_string($field) || $field === '') {
                continue;
            }

            $direction = (int) $direction;
            if ($direction !== SortNode::SORT_ASC && $direction !== SortNode::SORT_DESC) {
                throw new DataStoreException('Invalid sort direction: ' . $direction);
            }

            $sort[] = [
                $field => $direction === SortNode::SORT_ASC ? 'asc' : 'desc',
            ];
        }

        return $sort;
    }

    /**
     * @param array[] $sort
     * @return array[]
     */
    private function appendSortTieBreaker(array $sort): array
    {
        if ($sort === [] && $this->identifier !== '_id') {
            $sort[] = [$this->identifier => 'asc'];
        }

        foreach ($sort as $sortPart) {
            if (isset($sortPart['_id'])) {
                return $sort;
            }
        }

        $sort[] = ['_id' => 'asc'];

        return $sort;
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

    private function generateIdentifier(): string
    {
        return bin2hex(random_bytes(16));
    }

    private function shouldUseNativeAggregations(Query $query): bool
    {
        $groupFields = $this->extractGroupByFields($query);
        if ($groupFields !== []) {
            return true;
        }

        $selectNode = $query->getSelect();

        return $this->hasAggregateSelect($selectNode) && !$this->hasPlainSelectFields($selectNode);
    }

    private function queryWithNativeAggregations(Query $query): array
    {
        $limitNode = $query->getLimit();
        $limit = $this->resolveLimit($limitNode);
        $offset = $limitNode ? max(0, (int) $limitNode->getOffset()) : 0;

        if ($limit < 0) {
            throw new DataStoreException('Query limit must be greater or equal to zero.');
        }

        if ($limit === 0) {
            return [];
        }

        $groupFields = $this->extractGroupByFields($query);

        if ($groupFields !== []) {
            return $this->queryGroupedAggregations($query, $groupFields, $limit, $offset);
        }

        return $this->queryMetricAggregations($query, $limit, $offset);
    }

    /**
     * @param Query $query
     * @param string[] $groupFields
     * @param int $limit
     * @param int $offset
     * @return array
     */
    private function queryGroupedAggregations(Query $query, array $groupFields, int $limit, int $offset): array
    {
        $selectDescriptors = $this->attachMetricAliases(
            $this->buildSelectDescriptors($query, $groupFields)
        );
        $metricAggregations = $this->buildMetricAggregations($selectDescriptors);
        $groupSources = $this->buildGroupSources($groupFields, $query->getSort());

        $result = [];
        $remaining = $limit === self::LIMIT_INFINITY ? null : $limit;
        $skipped = 0;
        $afterKey = null;

        while (true) {
            $composite = [
                'size' => self::SEARCH_BATCH_SIZE,
                'sources' => $groupSources['sources'],
            ];

            if ($afterKey !== null) {
                $composite['after'] = $afterKey;
            }

            $body = [
                'size' => 0,
                'query' => $this->rqlToElasticsearchDslAdapter->convert($query->getQuery()),
                'aggs' => [
                    'groupby' => [
                        'composite' => $composite,
                        'aggs' => $metricAggregations,
                    ],
                ],
            ];

            try {
                $response = $this->client->search([
                    'index' => $this->index,
                    'body' => $body,
                ]);
            } catch (Missing404Exception) {
                $this->logger->info('ElasticsearchDataStore query: index not found', [
                    'index' => $this->index,
                ]);
                return [];
            }

            if (!is_array($response)) {
                break;
            }

            $groupBy = $response['aggregations']['groupby'] ?? null;
            $buckets = is_array($groupBy) ? ($groupBy['buckets'] ?? null) : null;

            if (!is_array($buckets) || $buckets === []) {
                break;
            }

            foreach ($buckets as $bucket) {
                if (!is_array($bucket)) {
                    continue;
                }

                if ($skipped < $offset) {
                    $skipped++;
                    continue;
                }

                $result[] = $this->hydrateGroupedResultRow(
                    $bucket,
                    $selectDescriptors,
                    $groupSources['byField']
                );

                if ($remaining !== null) {
                    $remaining--;
                    if ($remaining === 0) {
                        break 2;
                    }
                }
            }

            $afterKey = is_array($groupBy) ? ($groupBy['after_key'] ?? null) : null;

            if (!is_array($afterKey) || $afterKey === []) {
                break;
            }
        }

        return $this->normalizeResultSetShape($result);
    }

    private function queryMetricAggregations(Query $query, int $limit, int $offset): array
    {
        if ($offset > 0) {
            return [];
        }

        if ($limit !== self::LIMIT_INFINITY && $limit < 1) {
            return [];
        }

        $selectDescriptors = $this->attachMetricAliases(
            $this->buildSelectDescriptors($query, [])
        );
        $metricAggregations = $this->buildMetricAggregations($selectDescriptors);

        if ($metricAggregations === []) {
            return [];
        }

        $body = [
            'size' => 0,
            'query' => $this->rqlToElasticsearchDslAdapter->convert($query->getQuery()),
            'aggs' => $metricAggregations,
        ];

        try {
            $response = $this->client->search([
                'index' => $this->index,
                'body' => $body,
            ]);
        } catch (Missing404Exception) {
            $this->logger->info('ElasticsearchDataStore query: index not found', [
                'index' => $this->index,
            ]);
            return [];
        }

        if (!is_array($response)) {
            return [];
        }

        $aggregations = $response['aggregations'] ?? null;
        if (!is_array($aggregations)) {
            return [];
        }

        $row = [];
        foreach ($selectDescriptors as $descriptor) {
            if (($descriptor['type'] ?? null) !== 'metric') {
                continue;
            }

            $label = $descriptor['label'];
            $row[$label] = $this->extractMetricValue($aggregations, $descriptor);
        }

        if ($row === []) {
            return [];
        }

        return [$row];
    }

    /**
     * @param Query $query
     * @return string[]
     */
    private function extractGroupByFields(Query $query): array
    {
        if (!$query instanceof RqlQuery || $query->getGroupBy() === null) {
            return [];
        }

        $fields = [];
        foreach ($query->getGroupBy()->getFields() as $field) {
            if (!is_string($field) || $field === '') {
                continue;
            }

            $fields[] = $field;
        }

        return array_values(array_unique($fields));
    }

    /**
     * @param SelectNode|null $selectNode
     * @return bool
     */
    private function hasAggregateSelect(?SelectNode $selectNode): bool
    {
        if ($selectNode === null) {
            return false;
        }

        foreach ($selectNode->getFields() as $field) {
            if ($field instanceof AggregateFunctionNode) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param SelectNode|null $selectNode
     * @return bool
     */
    private function hasPlainSelectFields(?SelectNode $selectNode): bool
    {
        if ($selectNode === null) {
            return false;
        }

        foreach ($selectNode->getFields() as $field) {
            if (is_string($field) && $field !== '') {
                return true;
            }
        }

        return false;
    }

    private function shouldProcessSelectInMemory(Query $query): bool
    {
        $selectNode = $query->getSelect();

        if (!$this->hasAggregateSelect($selectNode)) {
            return false;
        }

        if ($query instanceof RqlQuery && $query->getGroupBy() !== null) {
            return false;
        }

        return $this->hasPlainSelectFields($selectNode);
    }

    /**
     * @param Query $query
     * @param string[] $groupFields
     * @return array<int,array<string,mixed>>
     */
    private function buildSelectDescriptors(Query $query, array $groupFields): array
    {
        $selectNode = $query->getSelect();

        if ($selectNode === null) {
            return array_map(static fn(string $field): array => [
                'type' => 'group',
                'field' => $field,
                'label' => $field,
            ], $groupFields);
        }

        $descriptors = [];

        foreach ($selectNode->getFields() as $field) {
            if ($field instanceof AggregateFunctionNode) {
                $function = strtolower((string) $field->getFunction());
                if (!in_array($function, ['count', 'max', 'min', 'sum', 'avg'], true)) {
                    throw new DataStoreException('Unsupported aggregate function: ' . $field->getFunction());
                }

                $descriptors[] = [
                    'type' => 'metric',
                    'function' => $function,
                    'field' => (string) $field->getField(),
                    'label' => $field->__toString(),
                ];
                continue;
            }

            if (!is_string($field) || $field === '') {
                continue;
            }

            if ($groupFields !== [] && in_array($field, $groupFields, true)) {
                $descriptors[] = [
                    'type' => 'group',
                    'field' => $field,
                    'label' => $field,
                ];
                continue;
            }

            if ($groupFields !== []) {
                $descriptors[] = [
                    'type' => 'metric',
                    'function' => 'count',
                    'field' => $field,
                    'label' => 'count(' . $field . ')',
                ];
            }
        }

        return $descriptors;
    }

    /**
     * @param array<int,array<string,mixed>> $selectDescriptors
     * @return array<int,array<string,mixed>>
     */
    private function attachMetricAliases(array $selectDescriptors): array
    {
        $index = 0;
        foreach ($selectDescriptors as $key => $descriptor) {
            if (($descriptor['type'] ?? null) !== 'metric') {
                continue;
            }

            $selectDescriptors[$key]['alias'] = 'metric_' . $index;
            $index++;
        }

        return $selectDescriptors;
    }

    /**
     * @param array<int,array<string,mixed>> $selectDescriptors
     * @return array<string,array>
     */
    private function buildMetricAggregations(array $selectDescriptors): array
    {
        $aggregations = [];

        foreach ($selectDescriptors as $descriptor) {
            if (($descriptor['type'] ?? null) !== 'metric') {
                continue;
            }

            $alias = $descriptor['alias'] ?? null;
            if (!is_string($alias) || $alias === '') {
                continue;
            }

            $field = (string) ($descriptor['field'] ?? '');
            $function = (string) ($descriptor['function'] ?? '');

            $aggregations[$alias] = match ($function) {
                'count' => $field === '_id'
                    ? ['filter' => ['match_all' => (object) []]]
                    : ['filter' => ['exists' => ['field' => $field]]],
                'max' => ['max' => ['field' => $field]],
                'min' => ['min' => ['field' => $field]],
                'sum' => ['sum' => ['field' => $field]],
                'avg' => ['avg' => ['field' => $field]],
                default => throw new DataStoreException('Unsupported aggregate function: ' . $function),
            };
        }

        return $aggregations;
    }

    /**
     * @param string[] $groupFields
     * @param SortNode|null $sortNode
     * @return array{sources: array<int,array<string,array>>, byField: array<string,string>}
     */
    private function buildGroupSources(array $groupFields, ?SortNode $sortNode): array
    {
        $directions = [];
        if ($sortNode !== null) {
            foreach ($sortNode->getFields() as $field => $direction) {
                if (!is_string($field) || $field === '') {
                    continue;
                }

                $direction = (int) $direction;
                if ($direction !== SortNode::SORT_ASC && $direction !== SortNode::SORT_DESC) {
                    throw new DataStoreException('Invalid sort direction: ' . $direction);
                }

                $directions[$field] = $direction === SortNode::SORT_ASC ? 'asc' : 'desc';
            }
        }

        $sources = [];
        $byField = [];

        foreach ($groupFields as $index => $field) {
            $sourceName = 'group_' . $index;
            $byField[$field] = $sourceName;
            $order = $directions[$field] ?? 'asc';

            $sources[] = [
                $sourceName => [
                    'terms' => [
                        'field' => $field,
                        'order' => $order,
                        'missing_bucket' => true,
                    ],
                ],
            ];
        }

        return [
            'sources' => $sources,
            'byField' => $byField,
        ];
    }

    /**
     * @param array $bucket
     * @param array<int,array<string,mixed>> $selectDescriptors
     * @param array<string,string> $groupFieldMap
     * @return array<string,mixed>
     */
    private function hydrateGroupedResultRow(array $bucket, array $selectDescriptors, array $groupFieldMap): array
    {
        $key = $bucket['key'] ?? [];
        if (!is_array($key)) {
            $key = [];
        }

        $row = [];

        foreach ($selectDescriptors as $descriptor) {
            $type = $descriptor['type'] ?? null;
            $label = (string) ($descriptor['label'] ?? '');

            if ($label === '') {
                continue;
            }

            if ($type === 'group') {
                $field = (string) ($descriptor['field'] ?? '');
                $sourceName = $groupFieldMap[$field] ?? null;
                $row[$label] = is_string($sourceName) ? ($key[$sourceName] ?? null) : null;
                continue;
            }

            if ($type === 'metric') {
                $row[$label] = $this->extractMetricValue($bucket, $descriptor);
            }
        }

        return $row;
    }

    /**
     * @param array<string,mixed> $aggregationContainer
     * @param array<string,mixed> $descriptor
     * @return mixed
     */
    private function extractMetricValue(array $aggregationContainer, array $descriptor): mixed
    {
        $alias = $descriptor['alias'] ?? null;
        if (!is_string($alias) || $alias === '') {
            return null;
        }

        $aggregation = $aggregationContainer[$alias] ?? null;
        if (!is_array($aggregation)) {
            return null;
        }

        $function = (string) ($descriptor['function'] ?? '');
        if ($function === 'count') {
            return (int) ($aggregation['doc_count'] ?? 0);
        }

        return $aggregation['value'] ?? null;
    }

    /**
     * @param array<int,array<string,mixed>> $result
     * @return array<int,array<string,mixed>>
     */
    private function normalizeResultSetShape(array $result): array
    {
        $itemField = [];
        foreach ($result as &$item) {
            if (!is_array($item)) {
                continue;
            }

            $keys = array_keys($item);
            $diff = array_diff($keys, $itemField);
            $itemField = array_merge($itemField, $diff);
            $diff = array_diff($itemField, $keys);

            foreach ($diff as $field) {
                $item[$field] = null;
            }
        }

        return $result;
    }

    private function queryWithInMemorySelect(Query $query): array
    {
        $limitNode = $query->getLimit();
        $limit = $this->resolveLimit($limitNode);
        $offset = $limitNode ? max(0, (int) $limitNode->getOffset()) : 0;

        if ($limit < 0) {
            throw new DataStoreException('Query limit must be greater or equal to zero.');
        }

        if ($limit === 0) {
            return [];
        }

        $rawQuery = new Query();
        if ($query->getQuery() !== null) {
            $rawQuery->setQuery($query->getQuery());
        }
        if ($query->getSort() !== null) {
            $rawQuery->setSort($query->getSort());
        }
        $rawQuery->setLimit(new LimitNode(self::LIMIT_INFINITY, 0));

        $data = $this->query($rawQuery);

        if ($query instanceof RqlQuery && $query->getGroupBy() !== null) {
            $result = $this->queryGroupBy($data, $query);
        } else {
            $result = $this->querySelect($data, $query);
        }

        $result = array_slice($result, $offset, $limit === self::LIMIT_INFINITY ? null : $limit);

        return $this->normalizeResultSetShape($result);
    }

    /**
     * @param array $hit
     * @param string[] $selectFields
     * @return array
     */
    private function normalizeSearchHit(array $hit, array $selectFields): array
    {
        $record = $hit['_source'] ?? [];

        if (!is_array($record)) {
            $record = [];
        }

        if (!array_key_exists($this->identifier, $record) && array_key_exists('_id', $hit)) {
            $record[$this->identifier] = $hit['_id'];
        }

        if ($selectFields === []) {
            return $record;
        }

        $selected = [];
        foreach ($selectFields as $field) {
            $selected[$field] = $record[$field] ?? null;
        }

        return $selected;
    }

    private function calculateBatchSize(?int $remaining, int $offset, int $skipped): int
    {
        if ($remaining === 0) {
            return 0;
        }

        $needToSkip = max(0, $offset - $skipped);
        $needToTake = $remaining ?? self::SEARCH_BATCH_SIZE;

        return max(1, min(self::SEARCH_BATCH_SIZE, $needToSkip + $needToTake));
    }

    private function resolveLimit(?LimitNode $limitNode): int
    {
        if ($limitNode === null) {
            return self::DEFAULT_QUERY_LIMIT;
        }

        return (int) $limitNode->getLimit();
    }
}
