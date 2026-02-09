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
