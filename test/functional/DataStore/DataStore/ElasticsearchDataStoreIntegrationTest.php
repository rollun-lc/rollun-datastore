<?php

declare(strict_types=1);

namespace rollun\test\functional\DataStore\DataStore;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use rollun\datastore\DataStore\ElasticsearchDataStore;
use rollun\datastore\Rql\RqlQuery;
use rollun\test\functional\FunctionalTestCase;
use Xiag\Rql\Parser\Node\LimitNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\EqNode;
use Xiag\Rql\Parser\Query;

class ElasticsearchDataStoreIntegrationTest extends FunctionalTestCase
{
    private const INDEX_NAME = 'all_logs-2026.06';

    private Client $client;
    private ElasticsearchDataStore $store;

    /** @var string[] */
    private array $createdIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $container = $this->getContainer();
        $containerClient = $container->get('ElasticSearchClient');

        if ($this->isClientReachable($containerClient)) {
            $this->client = $containerClient;
            $this->store = $container->get('ElasticLogDataStore');
            return;
        }

        $this->client = $this->buildReachableClientOrSkip();
        $this->store = new ElasticsearchDataStore($this->client, self::INDEX_NAME, '_id');
    }

    protected function tearDown(): void
    {
        $this->cleanupIndexedDocuments();
        parent::tearDown();
    }

    public function testWriteThenReadByIdAndQueryByDifferentFields(): void
    {
        $suffix = strtolower(bin2hex(random_bytes(6)));
        $service = 'it-service-' . $suffix;
        $message1 = 'itmsg' . $suffix . 'a';
        $message2 = 'itmsg' . $suffix . 'b';
        $id1 = 'it-log-' . $suffix . '-1';
        $id2 = 'it-log-' . $suffix . '-2';

        $this->indexLog($id1, $service, $message1, 'info');
        $this->indexLog($id2, $service, $message2, 'error');

        $record = $this->store->read($id1);
        $this->assertNotNull($record);
        $this->assertSame($id1, $record['_id']);
        $this->assertSame($service, $record['service']);
        $this->assertSame($message1, $record['message']);
        $this->assertSame('info', $record['level']);

        $queryByMessage = new Query();
        $queryByMessage->setQuery(new EqNode('message', $message2));
        $queryByMessage->setLimit(new LimitNode(5, 0));

        $foundByMessage = $this->store->query($queryByMessage);
        $this->assertCount(1, $foundByMessage);
        $this->assertSame($id2, $foundByMessage[0]['_id']);
        $this->assertSame($message2, $foundByMessage[0]['message']);

        $queryById = new Query();
        $queryById->setQuery(new EqNode('_id', $id1));
        $queryById->setLimit(new LimitNode(1, 0));

        $foundById = $this->store->query($queryById);
        $this->assertCount(1, $foundById);
        $this->assertSame($id1, $foundById[0]['_id']);
        $this->assertSame($message1, $foundById[0]['message']);

        $queryByService = new Query();
        $queryByService->setQuery(new EqNode('service.keyword', $service));
        $queryByService->setLimit(new LimitNode(10, 0));

        $foundByService = $this->store->query($queryByService);
        $this->assertCount(2, $foundByService);

        $actualIds = array_column($foundByService, '_id');
        sort($actualIds);
        $expectedIds = [$id1, $id2];
        sort($expectedIds);

        $this->assertSame($expectedIds, $actualIds);
    }

    public function testGroupByAndAggregateWithRealElastic(): void
    {
        $suffix = strtolower(bin2hex(random_bytes(6)));
        $service = 'it-group-' . $suffix;

        $this->indexLog('it-gb-' . $suffix . '-1', $service, 'group-msg-a', 'info', [
            'test_run' => $suffix,
            'test_number' => 10,
        ]);
        $this->indexLog('it-gb-' . $suffix . '-2', $service, 'group-msg-b', 'info', [
            'test_run' => $suffix,
            'test_number' => 30,
        ]);
        $this->indexLog('it-gb-' . $suffix . '-3', $service, 'group-msg-c', 'error', [
            'test_run' => $suffix,
            'test_number' => 7,
        ]);

        $query = new RqlQuery(
            'eq(test_run,' . $suffix . ')' .
            '&select(level.keyword,count(_id),max(test_number),min(test_number),sum(test_number),avg(test_number))' .
            '&groupby(level.keyword)'
        );

        $result = $this->store->query($query);
        $this->assertCount(2, $result);

        $byLevel = [];
        foreach ($result as $row) {
            $byLevel[$row['level.keyword']] = $row;
        }

        $this->assertArrayHasKey('info', $byLevel);
        $this->assertArrayHasKey('error', $byLevel);

        $this->assertSame(2, $byLevel['info']['count(_id)']);
        $this->assertEquals(30, $byLevel['info']['max(test_number)']);
        $this->assertEquals(10, $byLevel['info']['min(test_number)']);
        $this->assertEquals(40, $byLevel['info']['sum(test_number)']);
        $this->assertEquals(20, $byLevel['info']['avg(test_number)']);

        $this->assertSame(1, $byLevel['error']['count(_id)']);
        $this->assertEquals(7, $byLevel['error']['max(test_number)']);
        $this->assertEquals(7, $byLevel['error']['min(test_number)']);
        $this->assertEquals(7, $byLevel['error']['sum(test_number)']);
        $this->assertEquals(7, $byLevel['error']['avg(test_number)']);
    }

    private function indexLog(
        string $id,
        string $service,
        string $message,
        string $level,
        array $extraBody = []
    ): void
    {
        $this->createdIds[] = $id;

        $body = array_merge([
            '@timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'level' => $level,
            'service' => $service,
            'message' => $message,
            'context' => [
                'source' => __CLASS__,
                'tag' => 'integration-test',
            ],
        ], $extraBody);

        $this->client->index([
            'index' => self::INDEX_NAME,
            'id' => $id,
            'body' => $body,
            'refresh' => 'wait_for',
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
