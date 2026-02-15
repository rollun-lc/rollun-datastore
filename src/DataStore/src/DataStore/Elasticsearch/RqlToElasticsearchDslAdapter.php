<?php

declare(strict_types=1);

namespace rollun\datastore\DataStore\Elasticsearch;

use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\Rql\Node\BinaryNode\BinaryOperatorNodeAbstract;
use Xiag\Rql\Parser\DataType\Glob;
use Xiag\Rql\Parser\Node\AbstractQueryNode;
use Xiag\Rql\Parser\Node\Query\AbstractArrayOperatorNode;
use Xiag\Rql\Parser\Node\Query\AbstractLogicOperatorNode;
use Xiag\Rql\Parser\Node\Query\AbstractScalarOperatorNode;

final class RqlToElasticsearchDslAdapter
{
    /** @var array<string,string>|null */
    private ?array $fieldTypeCache = null;

    public function __construct(
        private readonly Client $client,
        private readonly string $index,
        private readonly string $identifier,
        private readonly LoggerInterface $logger = new NullLogger()
    ) {
    }

    public function convert(?AbstractQueryNode $queryNode): array
    {
        if ($queryNode === null) {
            return ['match_all' => (object) []];
        }

        if ($queryNode instanceof AbstractLogicOperatorNode) {
            $queries = [];
            foreach ($queryNode->getQueries() as $childQuery) {
                $queries[] = $this->convert($childQuery);
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

        if ($queryNode instanceof BinaryOperatorNodeAbstract) {
            return $this->buildBinaryQuery($queryNode);
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
                'like' => ['wildcard' => [$field => ['value' => $this->toWildcardPattern($queryNode->getValue())]]],
                'alike' => ['wildcard' => [$field => [
                    'value' => $this->toWildcardPattern($queryNode->getValue()),
                    'case_insensitive' => true,
                ]]],
                'contains' => ['wildcard' => [$field => ['value' => $this->toWildcardPattern($queryNode->getValue(), true)]]],
                default => throw new DataStoreException('The Scalar Operator not supported: ' . $scalarNodeName),
            };
        }

        throw new DataStoreException('The Node type not supported: ' . $queryNode->getNodeName());
    }

    private function buildBinaryQuery(BinaryOperatorNodeAbstract $queryNode): array
    {
        $field = $queryNode->getField();
        $nodeName = $queryNode->getNodeName();

        if ($field === $this->identifier) {
            return match ($nodeName) {
                'eqn', 'eqt', 'eqf', 'ie' => ['match_none' => (object) []],
                default => throw new DataStoreException('The Binary Operator not supported: ' . $nodeName),
            };
        }

        return match ($nodeName) {
            'eqn' => $this->buildIsNullQuery($field),
            'eqt' => ['term' => [$field => true]],
            'eqf' => ['term' => [$field => false]],
            'ie' => $this->buildIsEmptyQuery($field),
            default => throw new DataStoreException('The Binary Operator not supported: ' . $nodeName),
        };
    }

    private function buildIsEmptyQuery(string $field): array
    {
        $should = [$this->buildIsNullQuery($field)];
        $fieldType = $this->getFieldType($field);

        if ($fieldType === 'boolean') {
            $should[] = ['term' => [$field => false]];
        } elseif (in_array($fieldType, ['keyword', 'constant_keyword', 'wildcard', 'text'], true)) {
            $should[] = ['term' => [$field => '']];
        }

        return [
            'bool' => [
                'should' => $should,
                'minimum_should_match' => 1,
            ],
        ];
    }

    private function buildIsNullQuery(string $field): array
    {
        return [
            'bool' => [
                'must_not' => [
                    ['exists' => ['field' => $field]],
                ],
            ],
        ];
    }

    private function getFieldType(string $field): ?string
    {
        if ($this->fieldTypeCache === null) {
            $this->fieldTypeCache = $this->loadFieldTypeCache();
        }

        return $this->fieldTypeCache[$field] ?? null;
    }

    /**
     * @return array<string,string>
     */
    private function loadFieldTypeCache(): array
    {
        try {
            $mappingResponse = $this->client->indices()->getMapping([
                'index' => $this->index,
            ]);
        } catch (Missing404Exception) {
            return [];
        } catch (\Throwable $exception) {
            $this->logger->warning('ElasticsearchDataStore mapping read failed', [
                'index' => $this->index,
                'error' => $exception->getMessage(),
            ]);
            return [];
        }

        if (!is_array($mappingResponse) || $mappingResponse === []) {
            return [];
        }

        $indexMapping = $mappingResponse[$this->index]['mappings']['properties'] ?? null;

        if (!is_array($indexMapping)) {
            $firstMapping = reset($mappingResponse);
            if (is_array($firstMapping)) {
                $indexMapping = $firstMapping['mappings']['properties'] ?? null;
            }
        }

        if (!is_array($indexMapping)) {
            return [];
        }

        $result = [];
        $this->flattenFieldTypes($indexMapping, '', $result);

        return $result;
    }

    /**
     * @param array<string,mixed> $properties
     * @param string $prefix
     * @param array<string,string> $result
     * @return void
     */
    private function flattenFieldTypes(array $properties, string $prefix, array &$result): void
    {
        foreach ($properties as $name => $node) {
            if (!is_string($name) || !is_array($node)) {
                continue;
            }

            $path = $prefix === '' ? $name : $prefix . '.' . $name;

            if (isset($node['type']) && is_string($node['type']) && $node['type'] !== '') {
                $result[$path] = $node['type'];
            }

            if (isset($node['properties']) && is_array($node['properties'])) {
                $this->flattenFieldTypes($node['properties'], $path, $result);
            }

            if (isset($node['fields']) && is_array($node['fields'])) {
                $this->flattenFieldTypes($node['fields'], $path, $result);
            }
        }
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
}
