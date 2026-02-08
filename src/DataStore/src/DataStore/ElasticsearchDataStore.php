<?php
declare(strict_types=1);

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore;

use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use rollun\datastore\DataStore\Interfaces\ReadInterface;
use Xiag\Rql\Parser\DataType\Glob;
use Xiag\Rql\Parser\Node\AbstractQueryNode;
use Xiag\Rql\Parser\Node\LimitNode;
use Xiag\Rql\Parser\Node\Query\AbstractArrayOperatorNode;
use Xiag\Rql\Parser\Node\Query\AbstractLogicOperatorNode;
use Xiag\Rql\Parser\Node\Query\AbstractScalarOperatorNode;
use Xiag\Rql\Parser\Node\SelectNode;
use Xiag\Rql\Parser\Node\SortNode;
use Xiag\Rql\Parser\Query;

class ElasticsearchDataStore implements ReadInterface
{
    private const SEARCH_BATCH_SIZE = 500;

    public function __construct(
        private readonly Client $client,
        private readonly string $index,
        private readonly string $identifier = self::DEF_ID,
        private readonly LoggerInterface $logger = new NullLogger()
    ) {
    }

    public function getIdentifier()
    {
        return $this->identifier;
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
        $limitNode = $query->getLimit();
        $limit = $limitNode ? (int) $limitNode->getLimit() : self::LIMIT_INFINITY;
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
            'query' => $this->buildSearchQuery($query->getQuery()),
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

    protected function checkIdentifierType($id): void
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
        foreach ($sort as $sortPart) {
            if (isset($sortPart['_id'])) {
                return $sort;
            }
        }

        $sort[] = ['_id' => 'asc'];

        return $sort;
    }

    /**
     * @param AbstractQueryNode|null $queryNode
     * @return array
     */
    private function buildSearchQuery(?AbstractQueryNode $queryNode): array
    {
        if ($queryNode === null) {
            return ['match_all' => (object) []];
        }

        if ($queryNode instanceof AbstractLogicOperatorNode) {
            $queries = [];
            foreach ($queryNode->getQueries() as $childQuery) {
                $queries[] = $this->buildSearchQuery($childQuery);
            }

            if ($queries === []) {
                return ['match_all' => (object) []];
            }

            return match ($queryNode->getNodeName()) {
                'and' => ['bool' => ['must' => $queries]],
                'or' => ['bool' => ['should' => $queries, 'minimum_should_match' => 1]],
                'not' => ['bool' => ['must_not' => $queries]],
                default => throw new DataStoreException('The Logic Operator not supported: ' . $queryNode->getNodeName()),
            };
        }

        if ($queryNode instanceof AbstractArrayOperatorNode) {
            $field = $queryNode->getField();
            $values = array_map([$this, 'normalizeFieldValue'], $queryNode->getValues());
            $inQuery = $this->buildTermsQuery($field, $values);

            return match ($queryNode->getNodeName()) {
                'in' => $inQuery,
                'out' => ['bool' => ['must_not' => [$inQuery]]],
                default => throw new DataStoreException('The Array Operator not supported: ' . $queryNode->getNodeName()),
            };
        }

        if ($queryNode instanceof AbstractScalarOperatorNode) {
            $field = $queryNode->getField();
            $value = $this->normalizeFieldValue($queryNode->getValue());
            $scalarNodeName = $queryNode->getNodeName();

            return match ($scalarNodeName) {
                'eq' => $this->buildTermQuery($field, $value),
                'ne' => ['bool' => ['must_not' => [$this->buildTermQuery($field, $value)]]],
                'gt' => ['range' => [$field => ['gt' => $value]]],
                'ge' => ['range' => [$field => ['gte' => $value]]],
                'lt' => ['range' => [$field => ['lt' => $value]]],
                'le' => ['range' => [$field => ['lte' => $value]]],
                'like', 'alike' => ['wildcard' => [$field => ['value' => $this->toWildcardPattern($queryNode->getValue())]]],
                'contains' => ['wildcard' => [$field => ['value' => $this->toWildcardPattern($queryNode->getValue(), true)]]],
                default => throw new DataStoreException('The Scalar Operator not supported: ' . $scalarNodeName),
            };
        }

        throw new DataStoreException('The Node type not supported: ' . $queryNode->getNodeName());
    }

    private function buildTermQuery(string $field, mixed $value): array
    {
        if ($field !== $this->identifier) {
            return ['term' => [$field => $value]];
        }

        return [
            'bool' => [
                'should' => [
                    ['term' => [$field => $value]],
                    ['ids' => ['values' => [(string) $value]]],
                ],
                'minimum_should_match' => 1,
            ],
        ];
    }

    /**
     * @param string $field
     * @param array $values
     * @return array
     */
    private function buildTermsQuery(string $field, array $values): array
    {
        if ($field !== $this->identifier) {
            return ['terms' => [$field => $values]];
        }

        $ids = array_map(static fn($value) => (string) $value, $values);

        return [
            'bool' => [
                'should' => [
                    ['terms' => [$field => $values]],
                    ['ids' => ['values' => $ids]],
                ],
                'minimum_should_match' => 1,
            ],
        ];
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function normalizeFieldValue(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if ($value instanceof Glob) {
            return rawurldecode($this->extractGlobValue($value));
        }

        return $value;
    }

    /**
     * @param mixed $value
     * @param bool $contains
     * @return string
     */
    private function toWildcardPattern(mixed $value, bool $contains = false): string
    {
        $pattern = $value instanceof Glob
            ? $this->extractGlobValue($value)
            : (string) $this->normalizeFieldValue($value);

        $pattern = rawurldecode($pattern);

        if ($contains && !str_contains($pattern, '*') && !str_contains($pattern, '?')) {
            $pattern = '*' . $pattern . '*';
        }

        return $pattern;
    }

    private function extractGlobValue(Glob $glob): string
    {
        $reflection = new \ReflectionClass($glob);
        $globProperty = $reflection->getProperty('glob');
        $globProperty->setAccessible(true);
        $value = $globProperty->getValue($glob);
        $globProperty->setAccessible(false);

        return (string) $value;
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
}
