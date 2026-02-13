<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\functional\DataStore\Elasticsearch;

use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Elasticsearch\Namespaces\IndicesNamespace;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use rollun\datastore\DataStore\Elasticsearch\RqlToElasticsearchDslAdapter;
use rollun\datastore\Rql\Node\AlikeGlobNode;
use rollun\datastore\Rql\Node\BinaryNode\EqfNode;
use rollun\datastore\Rql\Node\BinaryNode\EqnNode;
use rollun\datastore\Rql\Node\BinaryNode\EqtNode;
use rollun\datastore\Rql\Node\BinaryNode\IeNode;
use rollun\datastore\Rql\Node\ContainsNode;
use Xiag\Rql\Parser\DataType\Glob;
use Xiag\Rql\Parser\Node\Query\ArrayOperator\InNode;
use Xiag\Rql\Parser\Node\Query\ArrayOperator\OutNode;
use Xiag\Rql\Parser\Node\Query\LogicOperator\AndNode;
use Xiag\Rql\Parser\Node\Query\LogicOperator\NotNode;
use Xiag\Rql\Parser\Node\Query\LogicOperator\OrNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\EqNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\GeNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\GtNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\LeNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\LikeNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\LtNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\NeNode;

/**
 * Functional tests for RqlToElasticsearchDslAdapter.
 *
 * Tests conversion of RQL query nodes to Elasticsearch DSL.
 * Analogous to RqlConditionBuilderTest.
 */
class RqlToElasticsearchDslAdapterTest extends TestCase
{
    private function createAdapter(
        ?array $mappingProperties = null,
        string $identifier = 'id'
    ): RqlToElasticsearchDslAdapter {
        $client = $this->createMock(Client::class);

        if ($mappingProperties === null) {
            $client->expects($this->never())->method('indices');
        } else {
            $indicesNamespace = $this->createMock(IndicesNamespace::class);
            $indicesNamespace->expects($this->any())
                ->method('getMapping')
                ->with(['index' => 'test-index'])
                ->willReturn([
                    'test-index' => [
                        'mappings' => [
                            'properties' => $mappingProperties,
                        ],
                    ],
                ]);

            $client->expects($this->any())
                ->method('indices')
                ->willReturn($indicesNamespace);
        }

        return new RqlToElasticsearchDslAdapter($client, 'test-index', $identifier, new NullLogger());
    }

    /**
     * Test null query converts to match_all
     */
    public function testNullQueryReturnsMatchAll(): void
    {
        $adapter = $this->createAdapter();
        $result = $adapter->convert(null);

        $this->assertCount(1, $result);
        $this->assertArrayHasKey('match_all', $result);
        $this->assertIsObject($result['match_all']);
    }

    /**
     * Data provider for scalar operators
     */
    public function scalarOperatorsProvider(): \Generator
    {
        yield 'eq with string' => [
            new EqNode('service', 'auth'),
            ['term' => ['service' => 'auth']],
        ];

        yield 'eq with string: prefix' => [
            new EqNode('code', '108693'),
            ['term' => ['code' => '108693']],
        ];

        yield 'eq with integer' => [
            new EqNode('count', 42),
            ['term' => ['count' => 42]],
        ];

        yield 'eq with float' => [
            new EqNode('price', 99.99),
            ['term' => ['price' => 99.99]],
        ];

        yield 'eq with boolean true' => [
            new EqNode('active', true),
            ['term' => ['active' => true]],
        ];

        yield 'eq with boolean false' => [
            new EqNode('active', false),
            ['term' => ['active' => false]],
        ];

        yield 'eq with null' => [
            new EqNode('field', null),
            ['term' => ['field' => null]],
        ];

        yield 'ne with string' => [
            new NeNode('status', 'closed'),
            ['bool' => ['must_not' => [['term' => ['status' => 'closed']]]]],
        ];

        yield 'ne with integer' => [
            new NeNode('count', 0),
            ['bool' => ['must_not' => [['term' => ['count' => 0]]]]],
        ];

        yield 'gt with integer' => [
            new GtNode('retries', 3),
            ['range' => ['retries' => ['gt' => 3]]],
        ];

        yield 'gt with float' => [
            new GtNode('price', 99.99),
            ['range' => ['price' => ['gt' => 99.99]]],
        ];

        yield 'ge with integer' => [
            new GeNode('score', 8),
            ['range' => ['score' => ['gte' => 8]]],
        ];

        yield 'ge with float' => [
            new GeNode('price', 8.8),
            ['range' => ['price' => ['gte' => 8.8]]],
        ];

        yield 'lt with integer' => [
            new LtNode('score', 5),
            ['range' => ['score' => ['lt' => 5]]],
        ];

        yield 'lt with float' => [
            new LtNode('price', 4.5),
            ['range' => ['price' => ['lt' => 4.5]]],
        ];

        yield 'le with integer' => [
            new LeNode('retries', 1),
            ['range' => ['retries' => ['lte' => 1]]],
        ];

        yield 'le with float' => [
            new LeNode('price', 10.0),
            ['range' => ['price' => ['lte' => 10.0]]],
        ];

        yield 'like with wildcard' => [
            new LikeNode('service', new Glob('a*')),
            ['wildcard' => ['service' => ['value' => 'a*']]],
        ];

        yield 'like with question mark' => [
            new LikeNode('code', new Glob('AB?')),
            ['wildcard' => ['code' => ['value' => 'AB?']]],
        ];

        yield 'alike (case insensitive)' => [
            new AlikeGlobNode('service', 'A*'),
            ['wildcard' => ['service' => ['value' => 'A*', 'case_insensitive' => true]]],
        ];

        yield 'contains' => [
            new ContainsNode('message', 'fail'),
            ['wildcard' => ['message' => ['value' => '*fail*']]],
        ];

        yield 'contains with string:0' => [
            new ContainsNode('id', '0'),
            ['wildcard' => ['id' => ['value' => '*0*']]],
        ];
    }

    /**
     * @dataProvider scalarOperatorsProvider
     */
    public function testScalarOperators($node, array $expected): void
    {
        $adapter = $this->createAdapter();
        $result = $adapter->convert($node);

        $this->assertSame($expected, $result);
    }

    /**
     * Data provider for array operators
     */
    public function arrayOperatorsProvider(): \Generator
    {
        yield 'in with strings' => [
            new InNode('owner', ['alice', 'bob']),
            ['terms' => ['owner' => ['alice', 'bob']]],
        ];

        yield 'in with integers' => [
            new InNode('count', [1, 2, 3]),
            ['terms' => ['count' => [1, 2, 3]]],
        ];

        yield 'in with mixed types' => [
            new InNode('tag', [2, 3.0, '004', true]),
            ['terms' => ['tag' => [2, 3.0, '004', true]]],
        ];

        yield 'out with strings' => [
            new OutNode('region', ['us', 'eu']),
            ['bool' => ['must_not' => [['terms' => ['region' => ['us', 'eu']]]]]],
        ];

        yield 'out with integers' => [
            new OutNode('status', [0, 1]),
            ['bool' => ['must_not' => [['terms' => ['status' => [0, 1]]]]]],
        ];
    }

    /**
     * @dataProvider arrayOperatorsProvider
     */
    public function testArrayOperators($node, array $expected): void
    {
        $adapter = $this->createAdapter();
        $result = $adapter->convert($node);

        $this->assertSame($expected, $result);
    }

    /**
     * Data provider for binary operators
     */
    public function binaryOperatorsProvider(): \Generator
    {
        yield 'eqn (is null)' => [
            new EqnNode('flag'),
            ['bool' => ['must_not' => [['exists' => ['field' => 'flag']]]]],
        ];

        yield 'eqt (is true)' => [
            new EqtNode('flag'),
            ['term' => ['flag' => true]],
        ];

        yield 'eqf (is false)' => [
            new EqfNode('flag'),
            ['term' => ['flag' => false]],
        ];
    }

    /**
     * @dataProvider binaryOperatorsProvider
     */
    public function testBinaryOperators($node, array $expected): void
    {
        $adapter = $this->createAdapter();
        $result = $adapter->convert($node);

        $this->assertSame($expected, $result);
    }

    /**
     * Test ie (is empty) operator for boolean field
     */
    public function testIeForBooleanField(): void
    {
        $adapter = $this->createAdapter(['flag' => ['type' => 'boolean']]);
        $result = $adapter->convert(new IeNode('flag'));

        $expected = [
            'bool' => [
                'should' => [
                    ['bool' => ['must_not' => [['exists' => ['field' => 'flag']]]]],
                    ['term' => ['flag' => false]],
                ],
                'minimum_should_match' => 1,
            ],
        ];

        $this->assertSame($expected, $result);
    }

    /**
     * Test ie (is empty) operator for keyword field
     */
    public function testIeForKeywordField(): void
    {
        $adapter = $this->createAdapter(['comment' => ['type' => 'keyword']]);
        $result = $adapter->convert(new IeNode('comment'));

        $expected = [
            'bool' => [
                'should' => [
                    ['bool' => ['must_not' => [['exists' => ['field' => 'comment']]]]],
                    ['term' => ['comment' => '']],
                ],
                'minimum_should_match' => 1,
            ],
        ];

        $this->assertSame($expected, $result);
    }

    /**
     * Data provider for logic operators
     */
    public function logicOperatorsProvider(): \Generator
    {
        yield 'and with two conditions' => [
            new AndNode([
                new EqNode('service', 'auth'),
                new EqNode('level', 'INFO'),
            ]),
            [
                'bool' => [
                    'must' => [
                        ['term' => ['service' => 'auth']],
                        ['term' => ['level' => 'INFO']],
                    ],
                ],
            ],
        ];

        yield 'or with two conditions' => [
            new OrNode([
                new EqNode('service', 'auth'),
                new EqNode('service', 'billing'),
            ]),
            [
                'bool' => [
                    'should' => [
                        ['term' => ['service' => 'auth']],
                        ['term' => ['service' => 'billing']],
                    ],
                    'minimum_should_match' => 1,
                ],
            ],
        ];

        yield 'not with condition' => [
            new NotNode([
                new EqNode('level', 'ERROR'),
            ]),
            [
                'bool' => [
                    'must_not' => [
                        ['term' => ['level' => 'ERROR']],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider logicOperatorsProvider
     */
    public function testLogicOperators($node, array $expected): void
    {
        $adapter = $this->createAdapter();
        $result = $adapter->convert($node);

        $this->assertSame($expected, $result);
    }

    /**
     * Test nested logic operators
     */
    public function testNestedLogicOperators(): void
    {
        $node = new AndNode([
            new OrNode([
                new EqNode('service', 'auth'),
                new EqNode('service', 'billing'),
            ]),
            new NotNode([
                new OrNode([
                    new EqNode('level', 'ERROR'),
                    new OutNode('region', ['us']),
                ]),
            ]),
        ]);

        $expected = [
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
                                [
                                    'bool' => [
                                        'should' => [
                                            ['term' => ['level' => 'ERROR']],
                                            ['bool' => ['must_not' => [['terms' => ['region' => ['us']]]]]],
                                        ],
                                        'minimum_should_match' => 1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $adapter = $this->createAdapter();
        $result = $adapter->convert($node);

        $this->assertSame($expected, $result);
    }

    /**
     * Test identifier field uses dual strategy (terms + ids)
     */
    public function testIdentifierFieldUsesDualStrategy(): void
    {
        $adapter = $this->createAdapter(null, 'id');

        // Test eq on identifier field
        $result = $adapter->convert(new EqNode('id', 123));

        $expected = [
            'bool' => [
                'should' => [
                    ['term' => ['id' => 123]],
                    ['ids' => ['values' => ['123']]],
                ],
                'minimum_should_match' => 1,
            ],
        ];

        $this->assertSame($expected, $result);

        // Test in on identifier field
        $result = $adapter->convert(new InNode('id', [1, 2, 3]));

        $expected = [
            'bool' => [
                'should' => [
                    ['terms' => ['id' => [1, 2, 3]]],
                    ['ids' => ['values' => ['1', '2', '3']]],
                ],
                'minimum_should_match' => 1,
            ],
        ];

        $this->assertSame($expected, $result);
    }

    /**
     * Test field type detection when mapping is unavailable
     */
    public function testFieldTypeDetectionWithUnavailableMapping(): void
    {
        $client = $this->createMock(Client::class);
        $indicesNamespace = $this->createMock(IndicesNamespace::class);
        $indicesNamespace->expects($this->once())
            ->method('getMapping')
            ->willThrowException(new Missing404Exception('Index not found'));

        $client->expects($this->once())
            ->method('indices')
            ->willReturn($indicesNamespace);

        $adapter = new RqlToElasticsearchDslAdapter($client, 'test-index', 'id', new NullLogger());

        // Should only check for null when mapping is unavailable (no field type known)
        $result = $adapter->convert(new IeNode('field'));

        $expected = [
            'bool' => [
                'should' => [
                    ['bool' => ['must_not' => [['exists' => ['field' => 'field']]]]],
                ],
                'minimum_should_match' => 1,
            ],
        ];

        $this->assertSame($expected, $result);
    }
}
