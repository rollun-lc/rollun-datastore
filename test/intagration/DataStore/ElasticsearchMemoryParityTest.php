<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\intagration\DataStore;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\ElasticsearchDataStore;
use rollun\datastore\DataStore\Memory;
use rollun\datastore\Rql\RqlQuery;

class ElasticsearchMemoryParityTest extends TestCase
{
    private static ?Memory $sharedMemoryDataStore = null;
    private static ?ElasticsearchDataStore $sharedElasticsearchDataStore = null;
    private static ?Client $sharedClient = null;
    private static ?string $sharedIndexName = null;
    private static bool $sharedInitialized = false;

    private Memory $memoryDataStore;
    private ElasticsearchDataStore $elasticsearchDataStore;
    private Client $client;
    private string $indexName;

    protected function setUp(): void
    {
        parent::setUp();

        if (!self::$sharedInitialized) {
            $this->initializeSharedDataStores();
        }

        $this->client = self::$sharedClient;
        $this->indexName = self::$sharedIndexName;
        $this->memoryDataStore = self::$sharedMemoryDataStore;
        $this->elasticsearchDataStore = self::$sharedElasticsearchDataStore;
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$sharedClient !== null && self::$sharedIndexName !== null) {
            try {
                self::$sharedClient->indices()->delete(['index' => self::$sharedIndexName]);
            } catch (Missing404Exception) {
            } catch (\Throwable) {
            }
        }

        self::$sharedMemoryDataStore = null;
        self::$sharedElasticsearchDataStore = null;
        self::$sharedClient = null;
        self::$sharedIndexName = null;
        self::$sharedInitialized = false;
    }

    /**
     * @dataProvider providerRqlParityCases
     * @param array{input:string, expected:array<int,array<string,mixed>>} $case
     * @throws DataStoreException
     */
    public function testRqlParityWithExpectedResults(array $case): void
    {
        $rql = $case['input'];
        $expected = $case['expected'];

        $memoryResult = $this->memoryDataStore->query(new RqlQuery($rql));
        $elasticResult = $this->elasticsearchDataStore->query(new RqlQuery($rql));

        $normalizedMemory = $this->normalizeRowsToExpectedShape($memoryResult, $expected);
        $normalizedElastic = $this->normalizeRowsToExpectedShape($elasticResult, $expected);

        $this->assertEquals($expected, $normalizedMemory, "Memory mismatch for RQL '{$rql}'");
        $this->assertEquals($expected, $normalizedElastic, "Elasticsearch mismatch for RQL '{$rql}'");
        $this->assertEquals($normalizedMemory, $normalizedElastic, "Parity mismatch for RQL '{$rql}'");
    }

    /**
     * @return array<string,array{0:array{input:string, expected:array<int,array<string,mixed>>}}>
     */
    public function providerRqlParityCases(): array
    {
        return [
            'empty_rql_returns_all' => [[
                'input' => '',
                'expected' => $this->idRows([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]),
            ]],
            'empty_filter_with_limit_offset' => [[
                'input' => 'select(id)&sort(id)&limit(4,3)',
                'expected' => $this->idRows([4, 5, 6, 7]),
            ]],
            'eq' => [[
                'input' => 'eq(service,auth)&select(id)&sort(id)',
                'expected' => $this->idRows([1, 2, 9]),
            ]],
            'ne' => [[
                'input' => 'ne(status,closed)&select(id)&sort(id)',
                'expected' => $this->idRows([1, 2, 4, 6, 7, 10, 12]),
            ]],
            'gt' => [[
                'input' => 'gt(retries,3)&select(id)&sort(id)',
                'expected' => $this->idRows([5, 7, 10]),
            ]],
            'ge' => [[
                'input' => 'ge(score,8.8)&select(id)&sort(id)',
                'expected' => $this->idRows([1, 4, 6, 12]),
            ]],
            'lt' => [[
                'input' => 'lt(score,4.5)&select(id)&sort(id)',
                'expected' => $this->idRows([5, 8, 10]),
            ]],
            'le' => [[
                'input' => 'le(retries,1)&select(id)&sort(id)',
                'expected' => $this->idRows([1, 3, 6, 9, 11, 12]),
            ]],
            'like' => [[
                'input' => 'like(service,a*)&select(id)&sort(id)',
                'expected' => $this->idRows([1, 2, 7, 8, 9, 12]),
            ]],
            'alike' => [[
                'input' => 'alike(service,A*)&select(id)&sort(id)',
                'expected' => $this->idRows([1, 2, 7, 8, 9, 12]),
            ]],
            'contains' => [[
                'input' => 'contains(message,fail)&select(id)&sort(id)',
                'expected' => $this->idRows([2, 7, 10]),
            ]],
            'in' => [[
                'input' => 'in(owner,(alice,bob))&select(id)&sort(id)',
                'expected' => $this->idRows([1, 2, 3, 7, 8]),
            ]],
            'out' => [[
                'input' => 'out(region,(us))&select(id)&sort(id)',
                'expected' => $this->idRows([2, 4, 6, 8, 10, 12]),
            ]],
            'eqt' => [[
                'input' => 'eqt(flag)&select(id)&sort(id)',
                'expected' => $this->idRows([1, 4, 7, 10]),
            ]],
            'eqf' => [[
                'input' => 'eqf(flag)&select(id)&sort(id)',
                'expected' => $this->idRows([2, 3, 5, 6, 8, 9, 11, 12]),
            ]],
            'eqn' => [[
                'input' => 'eqn(comment)&select(id)&sort(id)',
                'expected' => $this->idRows([3, 6, 9, 12]),
            ]],
            'ie_bool' => [[
                'input' => 'ie(flag)&select(id)&sort(id)',
                'expected' => $this->idRows([2, 3, 5, 6, 8, 9, 11, 12]),
            ]],
            'ie_string' => [[
                'input' => 'ie(comment)&select(id)&sort(id)',
                'expected' => $this->idRows([2, 3, 5, 6, 8, 9, 11, 12]),
            ]],
            'identifier_eq' => [[
                'input' => 'eq(id,4)&select(id)&sort(id)',
                'expected' => $this->idRows([4]),
            ]],
            'identifier_in' => [[
                'input' => 'in(id,(2,5,12))&select(id)&sort(id)',
                'expected' => $this->idRows([2, 5, 12]),
            ]],
            'and_or_not_nested' => [[
                'input' => 'and(or(eq(service,auth),eq(service,billing)),not(out(region,(us))),ie(flag))&select(id)&sort(id)',
                'expected' => $this->idRows([3, 9]),
            ]],
            'nested_or_and' => [[
                'input' => 'or(and(eq(service,search),gt(retries,0)),and(eq(service,analytics),lt(score,5)))&select(id)&sort(id)',
                'expected' => $this->idRows([5, 8, 11]),
            ]],
            'select_limit_sort' => [[
                'input' => 'eq(service,auth)&select(id,service)&sort(-id)&limit(2,1)',
                'expected' => [
                    ['id' => 2, 'service' => 'auth'],
                    ['id' => 1, 'service' => 'auth'],
                ],
            ]],
            'aggregate' => [[
                'input' => 'select(count(id),max(id),min(id),sum(id),avg(id))',
                'expected' => [[
                    'count(id)' => 12,
                    'max(id)' => 12,
                    'min(id)' => 1,
                    'sum(id)' => 78,
                    'avg(id)' => 6.5,
                ]],
            ]],
            'groupby_with_aggregate' => [[
                'input' => 'select(team,count(id))&groupby(team)&sort(team)',
                'expected' => [
                    ['team' => 'core', 'count(id)' => 3],
                    ['team' => 'data', 'count(id)' => 3],
                    ['team' => 'payments', 'count(id)' => 3],
                    ['team' => 'search', 'count(id)' => 3],
                ],
            ]],
            'groupby_with_default_count' => [[
                'input' => 'select(team,id)&groupby(team)&sort(team)',
                'expected' => [
                    ['team' => 'core', 'count(id)' => 3],
                    ['team' => 'data', 'count(id)' => 3],
                    ['team' => 'payments', 'count(id)' => 3],
                    ['team' => 'search', 'count(id)' => 3],
                ],
            ]],
            'deep_logic_mix' => [[
                'input' => 'and(or(and(in(service,(auth,billing)),ge(score,7)),and(eq(service,analytics),or(eq(level,ERROR),eq(level,WARN)))),not(and(eq(status,closed),out(region,(us)))),or(eqt(flag),ie(comment)))&select(id)&sort(id)',
                'expected' => $this->idRows([1, 3, 4, 7, 9]),
            ]],
            'double_negation_and_arrays' => [[
                'input' => 'and(not(or(and(eq(service,search),lt(score,8)),and(eq(service,auth),gt(retries,0)))),out(owner,(alice,bob)),ge(retries,1))&select(id)&sort(id)',
                'expected' => $this->idRows([4, 10]),
            ]],
            'complex_sort_limit_projection' => [[
                'input' => 'or(and(contains(message,pipeline),out(status,(closed))),and(contains(message,payment),eq(level,ERROR)),and(eq(service,auth),not(eqt(flag))))&select(id,service,level,status)&sort(service,-score,id)&limit(3,1)',
                'expected' => [
                    ['id' => 9, 'service' => 'auth', 'level' => 'INFO', 'status' => 'closed'],
                    ['id' => 2, 'service' => 'auth', 'level' => 'ERROR', 'status' => 'open'],
                    ['id' => 10, 'service' => 'billing', 'level' => 'ERROR', 'status' => 'open'],
                ],
            ]],
            'aggregate_with_complex_filter' => [[
                'input' => 'and(or(eq(team,core),eq(team,payments)),not(lt(score,5)),out(status,(closed)))&select(count(id),sum(retries),avg(score),max(id),min(id))',
                'expected' => [[
                    'count(id)' => 3,
                    'sum(retries)' => 5,
                    'avg(score)' => 8.166666666666666,
                    'max(id)' => 4,
                    'min(id)' => 1,
                ]],
            ]],
            'groupby_multi_field_with_filter' => [[
                'input' => 'and(or(eq(region,us),and(eq(region,eu),ge(score,5))),out(service,(billing)))&select(region,level,id)&groupby(region,level)&sort(region,level)',
                'expected' => [
                    ['region' => 'eu', 'level' => 'ERROR', 'count(id)' => 1],
                    ['region' => 'eu', 'level' => 'INFO', 'count(id)' => 2],
                    ['region' => 'us', 'level' => 'ERROR', 'count(id)' => 2],
                    ['region' => 'us', 'level' => 'INFO', 'count(id)' => 3],
                ],
            ]],
            'very_deep_logic_with_identifier_filter' => [[
                'input' => 'and(or(not(in(id,(1,2,3,4))),and(eq(service,billing),not(eq(status,closed)))),or(and(ie(comment),out(level,(WARN))),and(eqt(flag),in(owner,(alice,carol,george)))))&select(id)&sort(-id)',
                'expected' => $this->idRows([12, 11, 10, 9, 7, 6, 5, 4]),
            ]],
        ];
    }

    /**
     * @param int[] $ids
     * @return array<int,array{id:int}>
     */
    private function idRows(array $ids): array
    {
        return array_map(static fn(int $id): array => ['id' => $id], $ids);
    }

    /**
     * @param array<int,array<string,mixed>> $result
     * @param array<int,array<string,mixed>> $expected
     * @return array<int,array<string,mixed>>
     */
    private function normalizeRowsToExpectedShape(array $result, array $expected): array
    {
        if ($expected === []) {
            return $result;
        }

        $expectedKeys = array_keys($expected[0]);
        $normalized = [];

        foreach ($result as $row) {
            $normalizedRow = [];
            foreach ($expectedKeys as $key) {
                $normalizedRow[$key] = $row[$key] ?? null;
            }
            $normalized[] = $normalizedRow;
        }

        return $normalized;
    }

    private function seedDataStores(): void
    {
        foreach ($this->dataset() as $item) {
            $this->memoryDataStore->create($item);
            $this->elasticsearchDataStore->create($item);
        }
    }

    private function createIndex(): void
    {
        $this->client->indices()->create([
            'index' => $this->indexName,
            'body' => [
                'settings' => [
                    'number_of_shards' => 1,
                    'number_of_replicas' => 0,
                ],
                'mappings' => [
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'service' => ['type' => 'keyword'],
                        'message' => ['type' => 'keyword'],
                        'level' => ['type' => 'keyword'],
                        'status' => ['type' => 'keyword'],
                        'owner' => ['type' => 'keyword'],
                        'retries' => ['type' => 'integer'],
                        'score' => ['type' => 'float'],
                        'team' => ['type' => 'keyword'],
                        'region' => ['type' => 'keyword'],
                        'flag' => ['type' => 'boolean'],
                        'comment' => ['type' => 'keyword'],
                    ],
                ],
            ],
        ]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function dataset(): array
    {
        return [
            ['id' => 1, 'service' => 'auth', 'message' => 'login success', 'level' => 'INFO', 'status' => 'open', 'owner' => 'alice', 'retries' => 0, 'score' => 10.5, 'team' => 'core', 'region' => 'us', 'flag' => true, 'comment' => 'primary'],
            ['id' => 2, 'service' => 'auth', 'message' => 'login failed', 'level' => 'ERROR', 'status' => 'open', 'owner' => 'bob', 'retries' => 3, 'score' => 5.2, 'team' => 'core', 'region' => 'eu', 'flag' => false, 'comment' => ''],
            ['id' => 3, 'service' => 'billing', 'message' => 'invoice delayed', 'level' => 'WARN', 'status' => 'closed', 'owner' => 'alice', 'retries' => 1, 'score' => 7.0, 'team' => 'payments', 'region' => 'us', 'flag' => false, 'comment' => null],
            ['id' => 4, 'service' => 'billing', 'message' => 'invoice created', 'level' => 'INFO', 'status' => 'open', 'owner' => 'carol', 'retries' => 2, 'score' => 8.8, 'team' => 'payments', 'region' => 'eu', 'flag' => true, 'comment' => 'urgent'],
            ['id' => 5, 'service' => 'search', 'message' => 'query timeout', 'level' => 'ERROR', 'status' => 'closed', 'owner' => 'dave', 'retries' => 5, 'score' => 2.1, 'team' => 'search', 'region' => 'us', 'flag' => false, 'comment' => ''],
            ['id' => 6, 'service' => 'search', 'message' => 'index synced', 'level' => 'INFO', 'status' => 'open', 'owner' => 'erin', 'retries' => 0, 'score' => 9.4, 'team' => 'search', 'region' => 'eu', 'flag' => false, 'comment' => null],
            ['id' => 7, 'service' => 'analytics', 'message' => 'pipeline failed', 'level' => 'ERROR', 'status' => 'open', 'owner' => 'alice', 'retries' => 4, 'score' => 6.6, 'team' => 'data', 'region' => 'us', 'flag' => true, 'comment' => 'hotfix'],
            ['id' => 8, 'service' => 'analytics', 'message' => 'pipeline lagging', 'level' => 'WARN', 'status' => 'closed', 'owner' => 'bob', 'retries' => 2, 'score' => 4.4, 'team' => 'data', 'region' => 'eu', 'flag' => false, 'comment' => ''],
            ['id' => 9, 'service' => 'auth', 'message' => 'password changed', 'level' => 'INFO', 'status' => 'closed', 'owner' => 'frank', 'retries' => 1, 'score' => 8.1, 'team' => 'core', 'region' => 'us', 'flag' => false, 'comment' => null],
            ['id' => 10, 'service' => 'billing', 'message' => 'payment failed', 'level' => 'ERROR', 'status' => 'open', 'owner' => 'george', 'retries' => 6, 'score' => 3.9, 'team' => 'payments', 'region' => 'eu', 'flag' => true, 'comment' => 'daily'],
            ['id' => 11, 'service' => 'search', 'message' => 'search warmup', 'level' => 'INFO', 'status' => 'closed', 'owner' => 'helen', 'retries' => 1, 'score' => 7.7, 'team' => 'search', 'region' => 'us', 'flag' => false, 'comment' => ''],
            ['id' => 12, 'service' => 'analytics', 'message' => 'report generated', 'level' => 'INFO', 'status' => 'open', 'owner' => 'irene', 'retries' => 0, 'score' => 9.9, 'team' => 'data', 'region' => 'eu', 'flag' => false, 'comment' => null],
        ];
    }

    private function buildClient(): Client
    {
        $container = include './config/container.php';

        if ($container->has('ElasticSearchClient')) {
            return $container->get('ElasticSearchClient');
        }

        $hosts = array_values(array_filter([
            getenv('ELASTIC_HOST_1') ?: null,
            getenv('ELASTIC_HOST_2') ?: null,
            'http://elasticsearch:9200',
            'http://localhost:9200',
        ]));

        return ClientBuilder::create()->setHosts($hosts)->build();
    }

    private function isClientReachable(Client $client): bool
    {
        try {
            return $client->ping([
                'client' => [
                    'connect_timeout' => 2,
                    'timeout' => 2,
                ],
            ]);
        } catch (\Throwable) {
            return false;
        }
    }

    private function initializeSharedDataStores(): void
    {
        $client = $this->buildClient();

        if (!$this->isClientReachable($client)) {
            self::fail('Elasticsearch is unavailable for parity test.');
        }

        self::$sharedClient = $client;
        self::$sharedIndexName = 'test_int_rql_parity_' . strtolower(bin2hex(random_bytes(6)));

        $this->client = self::$sharedClient;
        $this->indexName = self::$sharedIndexName;

        $this->memoryDataStore = new Memory([
            'id',
            'service',
            'message',
            'level',
            'status',
            'owner',
            'retries',
            'score',
            'team',
            'region',
            'flag',
            'comment',
        ]);

        $this->elasticsearchDataStore = new ElasticsearchDataStore(
            $this->client,
            $this->indexName,
            'id',
            new NullLogger()
        );

        $this->createIndex();
        $this->seedDataStores();

        self::$sharedMemoryDataStore = $this->memoryDataStore;
        self::$sharedElasticsearchDataStore = $this->elasticsearchDataStore;
        self::$sharedInitialized = true;
    }
}
