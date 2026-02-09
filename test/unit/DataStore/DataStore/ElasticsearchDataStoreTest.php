<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\unit\DataStore\DataStore;

use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Conflict409Exception;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\ElasticsearchDataStore;
use rollun\datastore\DataStore\Interfaces\ReadInterface;
use rollun\datastore\Rql\RqlQuery;
use Xiag\Rql\Parser\Node\LimitNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\EqNode;
use Xiag\Rql\Parser\Node\SelectNode;
use Xiag\Rql\Parser\Node\SortNode;
use Xiag\Rql\Parser\Query;

class ElasticsearchDataStoreTest extends TestCase
{
    private function createObject(
        Client $client,
        LoggerInterface $logger = null,
        string $identifier = ReadInterface::DEF_ID
    ): ElasticsearchDataStore {
        $logger ??= new NullLogger();

        return new ElasticsearchDataStore($client, 'test-index', $identifier, $logger);
    }

    public function testCreateGeneratesIdentifierWhenMissing(): void
    {
        $capturedId = null;

        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('index')
            ->with($this->callback(function (array $params) use (&$capturedId): bool {
                $this->assertSame('test-index', $params['index']);
                $this->assertSame('create', $params['op_type']);
                $this->assertSame('wait_for', $params['refresh']);
                $this->assertArrayHasKey('id', $params);
                $this->assertNotSame('', $params['id']);
                $this->assertSame($params['id'], $params['body']['id']);
                $capturedId = $params['id'];

                return true;
            }))
            ->willReturn(['result' => 'created']);

        $client->expects($this->once())
            ->method('get')
            ->with($this->callback(function (array $params) use (&$capturedId): bool {
                return $params['index'] === 'test-index' && $params['id'] === $capturedId;
            }))
            ->willReturnCallback(static function () use (&$capturedId): array {
                return [
                    '_id' => $capturedId,
                    '_source' => [
                        'id' => $capturedId,
                        'message' => 'hello',
                    ],
                ];
            });

        $store = $this->createObject($client);

        $created = $store->create(['message' => 'hello']);
        $this->assertSame($capturedId, $created['id']);
        $this->assertSame('hello', $created['message']);
    }

    public function testCreateThrowsExceptionWhenRecordExists(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('index')
            ->willThrowException(new Conflict409Exception('conflict'));

        $store = $this->createObject($client);

        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage("Item with id 'doc-1' already exist");

        $store->create(['id' => 'doc-1', 'message' => 'hello']);
    }

    public function testCreateWithMetaIdentifierExcludesIdFromSource(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('index')
            ->with($this->callback(function (array $params): bool {
                $this->assertSame('doc-1', $params['id']);
                $this->assertArrayNotHasKey('_id', $params['body']);
                $this->assertSame('hello', $params['body']['message']);

                return true;
            }))
            ->willReturn(['result' => 'created']);

        $client->expects($this->once())
            ->method('get')
            ->willReturn([
                '_id' => 'doc-1',
                '_source' => [
                    'message' => 'hello',
                ],
            ]);

        $store = $this->createObject($client, null, '_id');
        $created = $store->create(['_id' => 'doc-1', 'message' => 'hello']);

        $this->assertSame('doc-1', $created['_id']);
    }

    public function testUpdateThrowsExceptionWhenPrimaryKeyMissing(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->never())
            ->method('index');

        $store = $this->createObject($client);

        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage('Item must has primary key');

        $store->update(['message' => 'hello']);
    }

    public function testUpdateThrowsExceptionWhenItemIsAbsent(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('get')
            ->willThrowException(new Missing404Exception('missing'));

        $store = $this->createObject($client);

        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage("[test-index]Can't update item with id = doc-1");

        $store->update(['id' => 'doc-1', 'message' => 'hello']);
    }

    public function testUpdateMergesStoredAndIncomingFields(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls(
                [
                    '_id' => 'doc-1',
                    '_source' => [
                        'id' => 'doc-1',
                        'message' => 'hello',
                        'level' => 'info',
                    ],
                ],
                [
                    '_id' => 'doc-1',
                    '_source' => [
                        'id' => 'doc-1',
                        'message' => 'hello',
                        'level' => 'error',
                    ],
                ]
            );

        $client->expects($this->once())
            ->method('index')
            ->with($this->callback(function (array $params): bool {
                $this->assertSame('doc-1', $params['id']);
                $this->assertSame('hello', $params['body']['message']);
                $this->assertSame('error', $params['body']['level']);
                $this->assertSame('doc-1', $params['body']['id']);

                return true;
            }))
            ->willReturn(['result' => 'updated']);

        $store = $this->createObject($client);

        $updated = $store->update([
            'id' => 'doc-1',
            'level' => 'error',
        ]);

        $this->assertSame('doc-1', $updated['id']);
        $this->assertSame('error', $updated['level']);
        $this->assertSame('hello', $updated['message']);
    }

    public function testDeleteReturnsDeletedRecord(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('get')
            ->willReturn([
                '_id' => 'doc-1',
                '_source' => [
                    'id' => 'doc-1',
                    'message' => 'hello',
                ],
            ]);

        $client->expects($this->once())
            ->method('delete')
            ->with([
                'index' => 'test-index',
                'id' => 'doc-1',
                'refresh' => 'wait_for',
            ])
            ->willReturn(['result' => 'deleted']);

        $store = $this->createObject($client);
        $deleted = $store->delete('doc-1');

        $this->assertSame('doc-1', $deleted['id']);
        $this->assertSame('hello', $deleted['message']);
    }

    public function testDeleteAllReturnsDeletedItemsCount(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('deleteByQuery')
            ->with($this->callback(function (array $params): bool {
                $this->assertSame('test-index', $params['index']);
                $this->assertTrue($params['refresh']);
                $this->assertSame('proceed', $params['conflicts']);

                return true;
            }))
            ->willReturn([
                'deleted' => 7,
            ]);

        $store = $this->createObject($client);

        $this->assertSame(7, $store->deleteAll());
    }

    public function testReadInjectsIdentifierFromElasticId(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('get')
            ->with([
                'index' => 'test-index',
                'id' => 'doc-1',
            ])
            ->willReturn([
                '_id' => 'doc-1',
                '_source' => [
                    'message' => 'hello',
                ],
            ]);

        $store = $this->createObject($client);
        $result = $store->read('doc-1');

        $this->assertSame([
            'message' => 'hello',
            'id' => 'doc-1',
        ], $result);
    }

    public function testReadReturnsNullForMissingDocument(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('get')
            ->willThrowException(new Missing404Exception('missing'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with(
                'ElasticsearchDataStore read: document not found',
                [
                    'index' => 'test-index',
                    'id' => 'doc-1',
                ]
            );

        $store = $this->createObject($client, $logger);

        $this->assertNull($store->read('doc-1'));
    }

    public function testHasReturnsTrueWhenDocumentExists(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('get')
            ->willReturn([
                '_id' => 'doc-1',
                '_source' => [
                    'id' => 'doc-1',
                ],
            ]);

        $store = $this->createObject($client);

        $this->assertTrue($store->has('doc-1'));
    }

    public function testQueryReturnsReadOnlyResult(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('search')
            ->with($this->callback(function (array $params): bool {
                $this->assertSame('test-index', $params['index']);
                $this->assertSame(1, $params['body']['size']);
                $this->assertSame(['id', 'message'], $params['body']['_source']);
                $this->assertSame([
                    ['id' => 'asc'],
                    ['_id' => 'asc'],
                ], $params['body']['sort']);
                $this->assertArrayHasKey('query', $params['body']);

                return true;
            }))
            ->willReturn([
                'hits' => [
                    'hits' => [
                        [
                            '_id' => 'doc-1',
                            '_source' => [
                                'message' => 'hello',
                            ],
                            'sort' => ['doc-1'],
                        ],
                    ],
                ],
            ]);

        $store = $this->createObject($client);

        $query = new Query();
        $query->setQuery(new EqNode('id', 'doc-1'));
        $query->setSelect(new SelectNode(['id', 'message']));
        $query->setSort(new SortNode(['id' => SortNode::SORT_ASC]));
        $query->setLimit(new LimitNode(1, 0));

        $this->assertSame([[
            'id' => 'doc-1',
            'message' => 'hello',
        ]], $store->query($query));
    }

    public function testQueryReturnsEmptyArrayWhenIndexNotFound(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('search')
            ->willThrowException(new Missing404Exception('missing index'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with(
                'ElasticsearchDataStore query: index not found',
                ['index' => 'test-index']
            );

        $store = $this->createObject($client, $logger);

        $this->assertSame([], $store->query(new Query()));
    }

    public function testCountReturnsValueFromElasticResponse(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('count')
            ->with($this->callback(function (array $params): bool {
                $this->assertSame('test-index', $params['index']);
                $this->assertArrayHasKey('body', $params);

                return true;
            }))
            ->willReturn([
                'count' => 25,
            ]);

        $store = $this->createObject($client);

        $this->assertSame(25, $store->count());
    }

    public function testQueryWithAggregateSelectUsesNativeAggregations(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('search')
            ->with($this->callback(function (array $params): bool {
                $this->assertSame('test-index', $params['index']);
                $this->assertSame(0, $params['body']['size']);
                $this->assertArrayHasKey('metric_0', $params['body']['aggs']);
                $this->assertArrayHasKey('metric_1', $params['body']['aggs']);
                $this->assertSame(
                    ['exists' => ['field' => 'id']],
                    $params['body']['aggs']['metric_0']['filter']
                );
                $this->assertSame(
                    ['field' => 'id'],
                    $params['body']['aggs']['metric_1']['max']
                );

                return true;
            }))
            ->willReturn([
                'aggregations' => [
                    'metric_0' => ['doc_count' => 3],
                    'metric_1' => ['value' => 3],
                    'metric_2' => ['value' => 1],
                    'metric_3' => ['value' => 6],
                    'metric_4' => ['value' => 2],
                ],
            ]);

        $store = $this->createObject($client);
        $result = $store->query(
            new RqlQuery('select(count(id),max(id),min(id),sum(id),avg(id))')
        );

        $this->assertEquals([
            [
                'count(id)' => 3,
                'max(id)' => 3,
                'min(id)' => 1,
                'sum(id)' => 6,
                'avg(id)' => 2,
            ],
        ], $result);
    }

    public function testQueryWithGroupByUsesCompositeAggregation(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('search')
            ->with($this->callback(function (array $params): bool {
                $this->assertSame('test-index', $params['index']);
                $this->assertSame(0, $params['body']['size']);
                $this->assertArrayHasKey('groupby', $params['body']['aggs']);

                $groupByAgg = $params['body']['aggs']['groupby'];
                $this->assertArrayHasKey('composite', $groupByAgg);
                $this->assertArrayHasKey('aggs', $groupByAgg);
                $this->assertCount(2, $groupByAgg['composite']['sources']);
                $this->assertArrayHasKey('metric_0', $groupByAgg['aggs']);
                $this->assertSame(
                    ['exists' => ['field' => 'id']],
                    $groupByAgg['aggs']['metric_0']['filter']
                );

                return true;
            }))
            ->willReturn([
                'aggregations' => [
                    'groupby' => [
                        'buckets' => [
                            [
                                'key' => [
                                    'group_0' => 'n1',
                                    'group_1' => 's1',
                                ],
                                'doc_count' => 2,
                                'metric_0' => ['doc_count' => 2],
                            ],
                            [
                                'key' => [
                                    'group_0' => 'n1',
                                    'group_1' => 's2',
                                ],
                                'doc_count' => 1,
                                'metric_0' => ['doc_count' => 1],
                            ],
                        ],
                    ],
                ],
            ]);

        $store = $this->createObject($client);
        $result = $store->query(
            new RqlQuery('select(name,surname,id)&groupby(name,surname)')
        );

        $this->assertEquals([
            ['name' => 'n1', 'surname' => 's1', 'count(id)' => 2],
            ['name' => 'n1', 'surname' => 's2', 'count(id)' => 1],
        ], $result);
    }
}
