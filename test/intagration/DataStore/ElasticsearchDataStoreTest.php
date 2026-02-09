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
            $this->client = $this->buildReachableClientOrSkip();
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

    private function buildReachableClientOrSkip(): Client
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

        $this->markTestSkipped('Elasticsearch is unavailable for integration test on all known hosts.');
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
