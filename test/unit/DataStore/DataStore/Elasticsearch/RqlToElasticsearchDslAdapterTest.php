<?php

declare(strict_types=1);

namespace rollun\test\unit\DataStore\DataStore\Elasticsearch;

use Elasticsearch\Client;
use Elasticsearch\Namespaces\IndicesNamespace;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use rollun\datastore\DataStore\Elasticsearch\RqlToElasticsearchDslAdapter;
use rollun\datastore\Rql\RqlQuery;
use Xiag\Rql\Parser\Node\AbstractQueryNode;

class RqlToElasticsearchDslAdapterTest extends TestCase
{
    public function testConvertNullQueryReturnsMatchAll(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->never())->method('indices');

        $adapter = $this->createAdapter($client);
        $dsl = $adapter->convert(null);
        $this->assertCount(1, $dsl);
        $this->assertArrayHasKey('match_all', $dsl);
        $this->assertIsObject($dsl['match_all']);
    }

    /**
     * @dataProvider providerScalarOperators
     */
    public function testConvertScalarOperators(string $rql, array $expectedDsl): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->never())->method('indices');

        $adapter = $this->createAdapter($client);
        $this->assertSame($expectedDsl, $adapter->convert($this->queryNode($rql)));
    }

    public function providerScalarOperators(): array
    {
        return [
            'eq' => [
                'eq(service,auth)',
                ['term' => ['service' => 'auth']],
            ],
            'ne' => [
                'ne(status,closed)',
                ['bool' => ['must_not' => [['term' => ['status' => 'closed']]]]],
            ],
            'gt' => [
                'gt(retries,3)',
                ['range' => ['retries' => ['gt' => 3]]],
            ],
            'ge' => [
                'ge(score,8.8)',
                ['range' => ['score' => ['gte' => 8.8]]],
            ],
            'lt' => [
                'lt(score,4.5)',
                ['range' => ['score' => ['lt' => 4.5]]],
            ],
            'le' => [
                'le(retries,1)',
                ['range' => ['retries' => ['lte' => 1]]],
            ],
            'like' => [
                'like(service,a*)',
                ['wildcard' => ['service' => ['value' => 'a*']]],
            ],
            'alike' => [
                'alike(service,A*)',
                ['wildcard' => ['service' => ['value' => 'A*', 'case_insensitive' => true]]],
            ],
            'contains' => [
                'contains(message,fail)',
                ['wildcard' => ['message' => ['value' => '*fail*']]],
            ],
        ];
    }

    /**
     * @dataProvider providerArrayOperators
     */
    public function testConvertArrayOperators(string $rql, array $expectedDsl): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->never())->method('indices');

        $adapter = $this->createAdapter($client);
        $this->assertSame($expectedDsl, $adapter->convert($this->queryNode($rql)));
    }

    public function providerArrayOperators(): array
    {
        return [
            'in' => [
                'in(owner,(alice,bob))',
                ['terms' => ['owner' => ['alice', 'bob']]],
            ],
            'out' => [
                'out(region,(us,eu))',
                ['bool' => ['must_not' => [['terms' => ['region' => ['us', 'eu']]]]]],
            ],
        ];
    }

    /**
     * @dataProvider providerBinaryOperators
     */
    public function testConvertBinaryOperators(string $rql, array $expectedDsl): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->never())->method('indices');

        $adapter = $this->createAdapter($client);
        $this->assertSame($expectedDsl, $adapter->convert($this->queryNode($rql)));
    }

    public function providerBinaryOperators(): array
    {
        return [
            'eqn' => [
                'eqn(flag)',
                ['bool' => ['must_not' => [['exists' => ['field' => 'flag']]]]],
            ],
            'eqt' => [
                'eqt(flag)',
                ['term' => ['flag' => true]],
            ],
            'eqf' => [
                'eqf(flag)',
                ['term' => ['flag' => false]],
            ],
        ];
    }

    /**
     * @dataProvider providerMetaIdBinaryOperators
     */
    public function testConvertMetaIdBinaryOperatorsToMatchNone(string $rql): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->never())->method('indices');

        $adapter = $this->createAdapter($client);
        $dsl = $adapter->convert($this->queryNode($rql));
        $this->assertCount(1, $dsl);
        $this->assertArrayHasKey('match_none', $dsl);
        $this->assertIsObject($dsl['match_none']);
    }

    public function providerMetaIdBinaryOperators(): array
    {
        return [
            ['eqn(_id)'],
            ['eqt(_id)'],
            ['eqf(_id)'],
            ['ie(_id)'],
        ];
    }

    public function testConvertIeForBooleanField(): void
    {
        $client = $this->createMock(Client::class);
        $indices = $this->createMock(IndicesNamespace::class);
        $indices->expects($this->once())
            ->method('getMapping')
            ->with(['index' => 'test-index'])
            ->willReturn([
                'test-index' => [
                    'mappings' => [
                        'properties' => [
                            'flag' => ['type' => 'boolean'],
                        ],
                    ],
                ],
            ]);
        $client->expects($this->once())->method('indices')->willReturn($indices);

        $adapter = $this->createAdapter($client);
        $this->assertSame([
            'bool' => [
                'should' => [
                    ['bool' => ['must_not' => [['exists' => ['field' => 'flag']]]]],
                    ['term' => ['flag' => false]],
                ],
                'minimum_should_match' => 1,
            ],
        ], $adapter->convert($this->queryNode('ie(flag)')));
    }

    public function testConvertIeForStringField(): void
    {
        $client = $this->createMock(Client::class);
        $indices = $this->createMock(IndicesNamespace::class);
        $indices->expects($this->once())
            ->method('getMapping')
            ->with(['index' => 'test-index'])
            ->willReturn([
                'test-index' => [
                    'mappings' => [
                        'properties' => [
                            'comment' => ['type' => 'keyword'],
                        ],
                    ],
                ],
            ]);
        $client->expects($this->once())->method('indices')->willReturn($indices);

        $adapter = $this->createAdapter($client);
        $this->assertSame([
            'bool' => [
                'should' => [
                    ['bool' => ['must_not' => [['exists' => ['field' => 'comment']]]]],
                    ['term' => ['comment' => '']],
                ],
                'minimum_should_match' => 1,
            ],
        ], $adapter->convert($this->queryNode('ie(comment)')));
    }

    public function testConvertNestedNodes(): void
    {
        $client = $this->createMock(Client::class);
        $indices = $this->createMock(IndicesNamespace::class);
        $indices->expects($this->once())
            ->method('getMapping')
            ->with(['index' => 'test-index'])
            ->willReturn([
                'test-index' => [
                    'mappings' => [
                        'properties' => [
                            'flag' => ['type' => 'boolean'],
                        ],
                    ],
                ],
            ]);
        $client->expects($this->once())->method('indices')->willReturn($indices);

        $adapter = $this->createAdapter($client);
        $rql = 'and(or(eq(service,auth),eq(service,billing)),not(out(region,(us))),ie(flag))';

        $this->assertSame([
            'bool' => [
                'must' => [
                    [
                        'bool' => [
                            'should' => [
                                ['term' => ['service' => 'auth']],
                                ['term' => ['service' => 'billing']],
                            ],
                            'minimum_should_match' => 1,
                        ],
                    ],
                    [
                        'bool' => [
                            'must_not' => [
                                ['bool' => ['must_not' => [['terms' => ['region' => ['us']]]]]],
                            ],
                        ],
                    ],
                    [
                        'bool' => [
                            'should' => [
                                ['bool' => ['must_not' => [['exists' => ['field' => 'flag']]]]],
                                ['term' => ['flag' => false]],
                            ],
                            'minimum_should_match' => 1,
                        ],
                    ],
                ],
            ],
        ], $adapter->convert($this->queryNode($rql)));
    }

    public function testConvertIdentifierEqForCustomIdentifier(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->never())->method('indices');

        $adapter = $this->createAdapter($client, 'id');
        $this->assertSame([
            'bool' => [
                'should' => [
                    ['term' => ['id' => 42]],
                    ['ids' => ['values' => ['42']]],
                ],
                'minimum_should_match' => 1,
            ],
        ], $adapter->convert($this->queryNode('eq(id,42)')));
    }

    public function testConvertIdentifierInForCustomIdentifier(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->never())->method('indices');

        $adapter = $this->createAdapter($client, 'id');
        $this->assertSame([
            'bool' => [
                'should' => [
                    ['terms' => ['id' => [1, 2, 3]]],
                    ['ids' => ['values' => ['1', '2', '3']]],
                ],
                'minimum_should_match' => 1,
            ],
        ], $adapter->convert($this->queryNode('in(id,(1,2,3))')));
    }

    // ========================================
    // Edge Case Tests
    // ========================================

    /**
     * Edge case: Wildcard pattern that already contains wildcards with contains().
     */
    public function testConvertContainsWithExistingWildcards(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->never())->method('indices');

        $adapter = $this->createAdapter($client);
        $result = $adapter->convert($this->queryNode('contains(message,*error*)'));

        // Should not add additional wildcards if already present
        $expected = ['wildcard' => ['message' => ['value' => '*error*']]];

        $this->assertSame($expected, $result);
    }

    /**
     * Edge case: like() with question mark wildcard.
     */
    public function testConvertLikeWithQuestionMarkWildcard(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->never())->method('indices');

        $adapter = $this->createAdapter($client);
        $result = $adapter->convert($this->queryNode('like(code,AB?)'));

        $expected = ['wildcard' => ['code' => ['value' => 'AB?']]];

        $this->assertSame($expected, $result);
    }

    /**
     * Edge case: Nested field mapping with dots.
     */
    public function testConvertIeForNestedField(): void
    {
        $client = $this->createMock(Client::class);
        $indices = $this->createMock(IndicesNamespace::class);
        $indices->expects($this->once())
            ->method('getMapping')
            ->with(['index' => 'test-index'])
            ->willReturn([
                'test-index' => [
                    'mappings' => [
                        'properties' => [
                            'user' => [
                                'properties' => [
                                    'profile' => [
                                        'properties' => [
                                            'bio' => ['type' => 'text'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]);
        $client->expects($this->once())->method('indices')->willReturn($indices);

        $adapter = $this->createAdapter($client);
        $result = $adapter->convert($this->queryNode('ie(user.profile.bio)'));

        $expected = [
            'bool' => [
                'should' => [
                    ['bool' => ['must_not' => [['exists' => ['field' => 'user.profile.bio']]]]],
                    ['term' => ['user.profile.bio' => '']],
                ],
                'minimum_should_match' => 1,
            ],
        ];

        $this->assertSame($expected, $result);
    }

    /**
     * Edge case: Field with .keyword suffix in mapping.
     */
    public function testConvertWithKeywordField(): void
    {
        $client = $this->createMock(Client::class);
        $indices = $this->createMock(IndicesNamespace::class);
        $indices->expects($this->once())
            ->method('getMapping')
            ->with(['index' => 'test-index'])
            ->willReturn([
                'test-index' => [
                    'mappings' => [
                        'properties' => [
                            'name' => [
                                'type' => 'text',
                                'fields' => [
                                    'keyword' => ['type' => 'keyword'],
                                ],
                            ],
                        ],
                    ],
                ],
            ]);
        $client->expects($this->once())->method('indices')->willReturn($indices);

        $adapter = $this->createAdapter($client);
        $result = $adapter->convert($this->queryNode('ie(name.keyword)'));

        $expected = [
            'bool' => [
                'should' => [
                    ['bool' => ['must_not' => [['exists' => ['field' => 'name.keyword']]]]],
                    ['term' => ['name.keyword' => '']],
                ],
                'minimum_should_match' => 1,
            ],
        ];

        $this->assertSame($expected, $result);
    }

    /**
     * Edge case: Mapping response with no properties.
     */
    public function testConvertIeWithEmptyMapping(): void
    {
        $client = $this->createMock(Client::class);
        $indices = $this->createMock(IndicesNamespace::class);
        $indices->expects($this->once())
            ->method('getMapping')
            ->with(['index' => 'test-index'])
            ->willReturn([
                'test-index' => [
                    'mappings' => [],
                ],
            ]);
        $client->expects($this->once())->method('indices')->willReturn($indices);

        $adapter = $this->createAdapter($client);
        $result = $adapter->convert($this->queryNode('ie(unknown_field)'));

        // Without field type info, should only check null
        $expected = [
            'bool' => [
                'should' => [
                    ['bool' => ['must_not' => [['exists' => ['field' => 'unknown_field']]]]],
                ],
                'minimum_should_match' => 1,
            ],
        ];

        $this->assertSame($expected, $result);
    }

    /**
     * Edge case: Identifier field with in() containing single value.
     */
    public function testConvertIdentifierInWithSingleValue(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->never())->method('indices');

        $adapter = $this->createAdapter($client, 'id');
        $result = $adapter->convert($this->queryNode('in(id,(42))'));

        $expected = [
            'bool' => [
                'should' => [
                    ['terms' => ['id' => [42]]],
                    ['ids' => ['values' => ['42']]],
                ],
                'minimum_should_match' => 1,
            ],
        ];

        $this->assertSame($expected, $result);
    }

    /**
     * Edge case: Multiple nested logic operators.
     */
    public function testConvertDeeplyNestedLogicOperators(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->never())->method('indices');

        $adapter = $this->createAdapter($client);
        $rql = 'and(or(and(eq(a,1),eq(b,2)),eq(c,3)),eq(d,4))';
        $result = $adapter->convert($this->queryNode($rql));

        $expected = [
            'bool' => [
                'must' => [
                    [
                        'bool' => [
                            'should' => [
                                [
                                    'bool' => [
                                        'must' => [
                                            ['term' => ['a' => 1]],
                                            ['term' => ['b' => 2]],
                                        ],
                                    ],
                                ],
                                ['term' => ['c' => 3]],
                            ],
                            'minimum_should_match' => 1,
                        ],
                    ],
                    ['term' => ['d' => 4]],
                ],
            ],
        ];

        $this->assertSame($expected, $result);
    }

    /**
     * Edge case: Range operators with negative numbers.
     */
    public function testConvertRangeOperatorsWithNegativeNumbers(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->never())->method('indices');

        $adapter = $this->createAdapter($client);
        $result = $adapter->convert($this->queryNode('gt(temperature,-10)'));

        $expected = ['range' => ['temperature' => ['gt' => -10]]];

        $this->assertSame($expected, $result);
    }

    /**
     * Edge case: Range operators with zero.
     */
    public function testConvertRangeOperatorsWithZero(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->never())->method('indices');

        $adapter = $this->createAdapter($client);
        $result = $adapter->convert($this->queryNode('ge(count,0)'));

        $expected = ['range' => ['count' => ['gte' => 0]]];

        $this->assertSame($expected, $result);
    }

    private function createAdapter(Client $client, string $identifier = '_id'): RqlToElasticsearchDslAdapter
    {
        return new RqlToElasticsearchDslAdapter($client, 'test-index', $identifier, new NullLogger());
    }

    private function queryNode(string $rql): ?AbstractQueryNode
    {
        return (new RqlQuery($rql))->getQuery();
    }
}
