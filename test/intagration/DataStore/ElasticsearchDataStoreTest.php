<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\intagration\DataStore;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use rollun\datastore\DataStore\DataStoreAbstract;
use rollun\datastore\DataStore\ElasticsearchDataStore;
use rollun\datastore\Rql\RqlQuery;
use Xiag\Rql\Parser\Node\LimitNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\EqNode;
use Xiag\Rql\Parser\Query;

class ElasticsearchDataStoreTest extends BaseDataStoreTest
{
    private const INDEX_NAME = 'test_datastore_elasticsearch';

    private Client $client;
    private ElasticsearchDataStore $store;

    /** @var string[] */
    private array $createdIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        global $container;
        $containerClient = $container->get('ElasticSearchClient');

        if ($this->isClientReachable($containerClient)) {
            $this->client = $containerClient;
        } else {
            $this->client = $this->buildReachableClientOrFail();
        }

        $this->ensureIndexExists();
        $this->store = new ElasticsearchDataStore($this->client, self::INDEX_NAME);
        $this->store->deleteAll();
    }

    protected function tearDown(): void
    {
        $this->cleanupIndexedDocuments();
        if (isset($this->store)) {
            $this->store->deleteAll();
        }
        parent::tearDown();
    }

    protected function createObject(): DataStoreAbstract
    {
        return $this->store;
    }

    protected function identifierToType($id)
    {
        return (int) $id;
    }

    public function testWriteThenReadAndQueryFromElastic(): void
    {
        $suffix = str_replace('.', '', uniqid('', true));
        $service = 'integration-service-' . $suffix;
        $message1 = 'integration-message-' . $suffix . '-1';
        $message2 = 'integration-message-' . $suffix . '-2';
        $id1 = 110001;
        $id2 = 110002;

        $this->indexLog($id1, $service, $message1, 'INFO');
        $this->indexLog($id2, $service, $message2, 'ERROR');

        $read = $this->store->read($id1);
        $this->assertNotNull($read);
        $this->assertSame($id1, $read['id']);
        $this->assertSame($service, $read['service']);
        $this->assertSame($message1, $read['message']);

        $queryByService = new Query();
        $queryByService->setQuery(new EqNode('service', $service));
        $queryByService->setLimit(new LimitNode(10, 0));

        $byService = $this->store->query($queryByService);
        $this->assertCount(2, $byService);

        $actualIds = array_column($byService, 'id');
        sort($actualIds);
        $expectedIds = [$id1, $id2];
        sort($expectedIds);
        $this->assertSame($expectedIds, $actualIds);

        $queryById = new Query();
        $queryById->setQuery(new EqNode('id', $id1));
        $queryById->setLimit(new LimitNode(1, 0));

        $byId = $this->store->query($queryById);
        $this->assertCount(1, $byId);
        $this->assertSame($id1, $byId[0]['id']);
        $this->assertSame($message1, $byId[0]['message']);
    }

    public function testGroupBySupportsCountAndMetrics(): void
    {
        $this->indexLog(210001, 'svc-a', 'msg-a-1', 'INFO');
        $this->indexLog(210002, 'svc-a', 'msg-a-2', 'INFO');
        $this->indexLog(210003, 'svc-b', 'msg-b-1', 'ERROR');
        $this->indexLog(210004, 'svc-b', 'msg-b-2', 'ERROR');
        $this->indexLog(210005, 'svc-b', 'msg-b-3', 'ERROR');

        $result = $this->store->query(
            new RqlQuery('select(service,count(id),max(id),min(id),sum(id),avg(id))&groupby(service)')
        );

        $byService = [];
        foreach ($result as $row) {
            $byService[$row['service']] = $row;
        }

        $this->assertArrayHasKey('svc-a', $byService);
        $this->assertArrayHasKey('svc-b', $byService);

        $this->assertSame(2, $byService['svc-a']['count(id)']);
        $this->assertEquals(210002, $byService['svc-a']['max(id)']);
        $this->assertEquals(210001, $byService['svc-a']['min(id)']);
        $this->assertEquals(420003, $byService['svc-a']['sum(id)']);
        $this->assertEquals(210001.5, $byService['svc-a']['avg(id)']);

        $this->assertSame(3, $byService['svc-b']['count(id)']);
        $this->assertEquals(210005, $byService['svc-b']['max(id)']);
        $this->assertEquals(210003, $byService['svc-b']['min(id)']);
        $this->assertEquals(630012, $byService['svc-b']['sum(id)']);
        $this->assertEquals(210004, $byService['svc-b']['avg(id)']);
    }

    public function testGroupByConvertsNonGroupedFieldToCount(): void
    {
        $this->store->create(['id' => 220001, 'name' => 'n1', 'surname' => 's1']);
        $this->store->create(['id' => 220002, 'name' => 'n1', 'surname' => 's1']);
        $this->store->create(['id' => 220003, 'name' => 'n1', 'surname' => 's2']);

        $result = $this->store->query(
            new RqlQuery('select(name,surname,id)&groupby(name,surname)')
        );

        $counts = [];
        foreach ($result as $row) {
            $this->assertArrayNotHasKey('id', $row);
            $this->assertArrayHasKey('count(id)', $row);

            $counts[$row['name'] . ':' . $row['surname']] = $row['count(id)'];
        }

        $this->assertSame(2, $counts['n1:s1']);
        $this->assertSame(1, $counts['n1:s2']);
    }

    /**
     * Data provider for type prefix tests
     * Format: [rqlQuery, expectedResults]
     */
    public function typeHintingDataProvider(): \Generator
    {
        // String type tests
        yield 'eq with string: explicit prefix' => [
            'eq(code,string:01)',
            [['id' => 310001, 'code' => '01']],
        ];
        yield 'eq with string: preserves leading zeros' => [
            'eq(code,string:001)',
            [['id' => 310003, 'code' => '001']],
        ];
        yield 'in with string: multiple values' => [
            'in(code,(string:01,string:02))',
            [['id' => 310001, 'code' => '01'], ['id' => 310002, 'code' => '02']],
        ];

        // Integer type tests
        yield 'eq with integer: on count field' => [
            'eq(count,integer:5)',
            [['id' => 310001, 'count' => 5]],
        ];
        yield 'gt with integer: comparison' => [
            'gt(count,integer:5)',
            [['id' => 310002, 'count' => 10], ['id' => 310003, 'count' => 7]],
        ];
        yield 'ge with integer: comparison' => [
            'ge(count,integer:7)',
            [['id' => 310002, 'count' => 10], ['id' => 310003, 'count' => 7]],
        ];
        yield 'lt with integer: comparison' => [
            'lt(count,integer:10)',
            [['id' => 310001, 'count' => 5], ['id' => 310003, 'count' => 7]],
        ];
        yield 'le with integer: comparison' => [
            'le(count,integer:7)',
            [['id' => 310001, 'count' => 5], ['id' => 310003, 'count' => 7]],
        ];

        // Float type tests
        yield 'eq with float: exact match' => [
            'eq(price,float:10.5)',
            [['id' => 310001, 'price' => 10.5]],
        ];
        yield 'gt with float: comparison' => [
            'gt(price,float:15)',
            [['id' => 310002, 'price' => 20.0], ['id' => 310003, 'price' => 15.75]],
        ];
        yield 'ge with float: comparison' => [
            'ge(price,float:15.75)',
            [['id' => 310002, 'price' => 20.0], ['id' => 310003, 'price' => 15.75]],
        ];
        yield 'lt with float: comparison' => [
            'lt(price,float:20)',
            [['id' => 310001, 'price' => 10.5], ['id' => 310003, 'price' => 15.75]],
        ];
        yield 'le with float: comparison' => [
            'le(price,float:15.75)',
            [['id' => 310001, 'price' => 10.5], ['id' => 310003, 'price' => 15.75]],
        ];

        // Boolean type tests
        yield 'eq with boolean:1 (true)' => [
            'eq(active,boolean:1)',
            [['id' => 310001, 'active' => true], ['id' => 310003, 'active' => true]],
        ];
        yield 'eq with boolean:0 (false)' => [
            'eq(active,boolean:0)',
            [['id' => 310002, 'active' => false]],
        ];
        yield 'ne with boolean:1 (not true)' => [
            'ne(active,boolean:1)',
            [['id' => 310002, 'active' => false]],
        ];

        // Mixed types in in() operator
        yield 'in with mixed types' => [
            'in(count,(5,integer:7,float:10))',
            [['id' => 310001, 'count' => 5], ['id' => 310002, 'count' => 10], ['id' => 310003, 'count' => 7]],
        ];
    }

    /**
     * @dataProvider typeHintingDataProvider
     * Test queries with type prefixes (string:, integer:, float:, boolean:)
     */
    public function testQueryWithTypeHinting(string $rqlQuery, array $expectedResults): void
    {
        // Create test data
        $this->store->create(['id' => 310001, 'code' => '01', 'price' => 10.5, 'count' => 5, 'active' => true]);
        $this->store->create(['id' => 310002, 'code' => '02', 'price' => 20.0, 'count' => 10, 'active' => false]);
        $this->store->create(['id' => 310003, 'code' => '001', 'price' => 15.75, 'count' => 7, 'active' => true]);

        $result = $this->store->query(new RqlQuery($rqlQuery));

        $this->assertCount(count($expectedResults), $result, "Query: {$rqlQuery}");

        // Sort both arrays by id for comparison
        usort($result, static fn($a, $b) => $a['id'] <=> $b['id']);
        usort($expectedResults, static fn($a, $b) => $a['id'] <=> $b['id']);

        foreach ($expectedResults as $index => $expected) {
            foreach ($expected as $key => $value) {
                $this->assertArrayHasKey($key, $result[$index], "Missing key '{$key}' in result for query: {$rqlQuery}");

                if (is_float($value)) {
                    $this->assertEquals($value, $result[$index][$key], "Value mismatch for '{$key}' in query: {$rqlQuery}", 0.001);
                } else {
                    $this->assertSame($value, $result[$index][$key], "Value mismatch for '{$key}' in query: {$rqlQuery}");
                }
            }
        }
    }

    public function testQueryWithTypePrefixesSupportsStringIntegerAndFloat(): void
    {
        // Create test data with numeric and string codes
        $this->store->create(['id' => 310001, 'code' => '01', 'price' => 10.5, 'count' => 5]);
        $this->store->create(['id' => 310002, 'code' => '02', 'price' => 20.0, 'count' => 10]);
        $this->store->create(['id' => 310003, 'code' => '001', 'price' => 15.75, 'count' => 7]);

        // Test string: prefix - should match exact string '01'
        $result = $this->store->query(new RqlQuery('eq(code,string:01)'));
        $this->assertCount(1, $result);
        $this->assertSame('01', $result[0]['code']);
        $this->assertSame(310001, $result[0]['id']);

        // Test integer: prefix on id field (uses dual strategy)
        $result = $this->store->query(new RqlQuery('eq(id,integer:310002)'));
        $this->assertCount(1, $result);
        $this->assertSame(310002, $result[0]['id']);

        // Test integer: prefix on count field
        $result = $this->store->query(new RqlQuery('ge(count,integer:7)'));
        $this->assertCount(2, $result);
        $counts = array_column($result, 'count');
        sort($counts);
        $this->assertSame([7, 10], $counts);

        // Test float: prefix
        $result = $this->store->query(new RqlQuery('gt(price,float:15)'));
        $this->assertCount(2, $result);
        $prices = array_column($result, 'price');
        sort($prices);
        $this->assertEquals([15.75, 20.0], $prices);

        // Test in() with string: prefix
        $result = $this->store->query(new RqlQuery('in(code,(string:01,string:02))'));
        $this->assertCount(2, $result);
        $codes = array_column($result, 'code');
        sort($codes);
        $this->assertSame(['01', '02'], $codes);

        // Test in() with integer: prefix on identifier field (uses dual strategy)
        $result = $this->store->query(new RqlQuery('in(id,(integer:310001,integer:310003))'));
        $this->assertCount(2, $result);
        $ids = array_column($result, 'id');
        sort($ids);
        $this->assertSame([310001, 310003], $ids);

        // Test mixed types in in() operator
        $result = $this->store->query(new RqlQuery('in(count,(5,integer:7,float:10))'));
        $this->assertCount(3, $result);
        $counts = array_column($result, 'count');
        sort($counts);
        $this->assertSame([5, 7, 10], $counts);
    }

    private function indexLog(int $id, string $service, string $message, string $level): void
    {
        $idAsString = (string) $id;
        $this->createdIds[] = $idAsString;

        $this->client->index([
            'index' => self::INDEX_NAME,
            'id' => $idAsString,
            'body' => [
                'id' => $id,
                '@timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
                'level' => $level,
                'service' => $service,
                'message' => $message,
                'context' => [
                    'source' => __CLASS__,
                    'tag' => 'integration-test',
                ],
            ],
            'refresh' => 'wait_for',
        ]);
    }

    private function ensureIndexExists(): void
    {
        if ($this->client->indices()->exists(['index' => self::INDEX_NAME])) {
            return;
        }

        $this->client->indices()->create([
            'index' => self::INDEX_NAME,
            'body' => [
                'mappings' => [
                    'properties' => [
                        'id' => ['type' => 'long'],
                        'name' => ['type' => 'keyword'],
                        'surname' => ['type' => 'keyword'],
                        'level' => ['type' => 'keyword'],
                        'service' => ['type' => 'keyword'],
                        'message' => ['type' => 'text', 'fields' => ['keyword' => ['type' => 'keyword']]],
                        '@timestamp' => ['type' => 'date'],
                    ],
                ],
            ],
        ]);
    }

    private function cleanupIndexedDocuments(): void
    {
        foreach ($this->createdIds as $id) {
            try {
                $this->client->delete([
                    'index' => self::INDEX_NAME,
                    'id' => $id,
                    'refresh' => 'wait_for',
                ]);
            } catch (Missing404Exception) {
            } catch (\Throwable) {
            }
        }

        $this->createdIds = [];
    }

    private function buildReachableClientOrFail(): Client
    {
        $hosts = array_values(array_unique(array_filter([
            getenv('ELASTIC_HOST_1') ?: null,
            getenv('ELASTIC_HOST_2') ?: null,
            'http://elasticsearch:9200',
            'http://localhost:9200',
            'http://host.docker.internal:9200',
        ])));

        foreach ($hosts as $host) {
            $client = ClientBuilder::create()->setHosts([$host])->build();
            if ($this->isClientReachable($client)) {
                return $client;
            }
        }

        self::fail('Elasticsearch is unavailable for integration test on all known hosts.');
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
}
