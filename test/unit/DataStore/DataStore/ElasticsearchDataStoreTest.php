<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\unit\DataStore\DataStore;

use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use rollun\datastore\DataStore\ElasticsearchDataStore;
use rollun\datastore\DataStore\Interfaces\ReadInterface;
use Xiag\Rql\Parser\Node\LimitNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\EqNode;
use Xiag\Rql\Parser\Node\SelectNode;
use Xiag\Rql\Parser\Node\SortNode;
use Xiag\Rql\Parser\Query;

class ElasticsearchDataStoreTest extends TestCase
{
    private function createObject(Client $client, LoggerInterface $logger = null): ElasticsearchDataStore
    {
        return new ElasticsearchDataStore($client, 'test-index', ReadInterface::DEF_ID, $logger);
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
}
