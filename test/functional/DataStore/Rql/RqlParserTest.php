<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\old\RqlParser;

use PHPUnit\Framework\TestCase;
use rollun\datastore\Rql\Node\AlikeGlobNode;
use rollun\datastore\Rql\Node\BinaryNode\EqfNode;
use rollun\datastore\Rql\Node\BinaryNode\EqnNode;
use rollun\datastore\Rql\Node\BinaryNode\EqtNode;
use rollun\datastore\Rql\Node\BinaryNode\IeNode;
use rollun\datastore\Rql\Node\ContainsNode;
use rollun\datastore\Rql\Node\GroupbyNode;
use rollun\datastore\Rql\RqlQuery;
use Xiag\Rql\Parser\Node\LimitNode;
use Xiag\Rql\Parser\Node\Query\ArrayOperator\InNode;
use Xiag\Rql\Parser\Node\Query\ArrayOperator\OutNode;
use Xiag\Rql\Parser\Node\Query\LogicOperator\AndNode;
use Xiag\Rql\Parser\Node\Query\LogicOperator\OrNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\EqNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\GeNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\GtNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\LeNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\LtNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\NeNode;
use Xiag\Rql\Parser\Node\SortNode;
use Xiag\Rql\Parser\Query;
use rollun\datastore\Rql\Node\AggregateFunctionNode;
use rollun\datastore\Rql\Node\AggregateSelectNode;
use rollun\datastore\Rql\RqlParser;

class RqlParserTest extends TestCase
{
    /** @var  RqlParser */
    private $object;

    /** @var  Query */
    private $queryObject;

    private $rqlString;

    protected function setUp(): void
    {
        $this->queryObject = new RqlQuery();

        $this->queryObject->setQuery(new AndNode([
            new AndNode([
                new EqNode('q', null),
                new NeNode('q', null),
                new LeNode('q', 'r'),
                new GeNode('q', 'u'),
                new EqnNode('a'),
                new EqfNode('b'),
                new EqtNode('c'),
                new AlikeGlobNode('d', '*abc?'),
                new IeNode('f'),
            ]),
            new OrNode([
                new LtNode('q', 't'),
                new GtNode('q', 'y'),
                new InNode('q', ['a', 's', 'd', 'f', 'g']),
            ]),
        ]));

        $this->queryObject->setSelect(new AggregateSelectNode([
            'q',
            (new AggregateFunctionNode('max', 'q')),
            (new AggregateFunctionNode('min', 'q')),
            (new AggregateFunctionNode('count', 'q')),
        ]));

        $this->queryObject->setSort(new SortNode(['q' => -1, 'w' => 1, 'e' => 1]));
        $this->queryObject->setLimit(new LimitNode(20, 30));

        $this->rqlString = "and(and(eq(q,null()),ne(q,null()),le(q,r),ge(q,u),eqn(a),eqf(b),eqt(c),alike(d,*abc?),ie(f)),or(lt(q,t),gt(q,y),in(q,(a,s,d,f,g))))";
        $this->rqlString .= "&limit(20,30)";
        $this->rqlString .= "&sort(-q,+w,e)";
        $this->rqlString .= "&select(q,max(q),min(q),count(q))";
    }

    public function testRqlDecoder()
    {
        $queryObject = RqlParser::rqlDecode($this->rqlString);
        $this->assertTrue(isset($queryObject));
        $this->assertEquals($this->queryObject, $queryObject);
    }

    public function testRqlEncode()
    {
        $this->rqlString = "and(and(eq(q,null()),ne(q,null()),le(q,string:r),ge(q,string:u),eqn(a),eqf(b),eqt(c),alike(d,string:*abc?),ie(f)),or(lt(q,string:t),gt(q,string:y),in(q,(string:a,string:s,string:d,string:f,string:g))))";
        $this->rqlString .= "&limit(20,30)";
        $this->rqlString .= "&sort(-q,+w,+e)";
        $this->rqlString .= "&select(q,max(q),min(q),count(q))";

        $rqlString = RqlParser::rqlEncode($this->queryObject);
        $this->assertEquals($rqlString, $this->rqlString);
    }

    public function testRqlEncodeLimit()
    {

        $queryObject = RqlParser::rqlDecode("limit(20)");
        $rqlString = RqlParser::rqlEncode($queryObject);
        $this->assertEquals("&limit(20)", $rqlString);
    }

    public function testRqlEncodeStringNodeEq()
    {
        $queryObject = RqlParser::rqlDecode("eq(a,string:01)");
        $rqlString = RqlParser::rqlEncode($queryObject);
        $this->assertEquals("eq(a,string:01)", $rqlString);
    }

    public function testPreparingQueryOneNode()
    {
        $rqlString = RqlParser::rqlDecode("eq(email,aaa@gmail.com)");
        $query = new RqlQuery();
        $query->setQuery(new EqNode('email', 'aaa@gmail.com'));
        $this->assertEquals($query, $rqlString);
    }

    public function testPreparingQueryInNode()
    {
        $rqlString = RqlParser::rqlDecode("in(email,(aaa@gmail.com,qwe,zxc))");
        $query = new RqlQuery();
        $query->setQuery(new InNode('email', ['aaa@gmail.com', 'qwe', 'zxc']));
        $this->assertEquals($query, $rqlString);
    }

    public function testPreparingQueryInsertedQuery()
    {
        $rqlString = RqlParser::rqlDecode('and(eq(email,aaa@gmail.com),or(le(age,1\,4),ge(age,1\.8)),ne(name,q1$3))');
        $query = new RqlQuery();
        $query->setQuery(new AndNode([
            new EqNode('email', 'aaa@gmail.com'),
            new OrNode([
                new LeNode('age', '1,4'),
                new GeNode('age', '1.8'),
            ]),
            new NeNode('name', 'q1$3'),
        ]));

        $this->assertEquals($query, $rqlString);
    }

    public function testPreparingQueryWithSelect()
    {
        $rqlString = RqlParser::rqlDecode("eq(email,aaa@gmail.com)&select(name,age,email)");
        $query = new RqlQuery();
        $query->setQuery(new EqNode('email', 'aaa@gmail.com'));
        $query->setSelect(new AggregateSelectNode(['name', 'age', 'email']));

        $this->assertEquals($query, $rqlString);
    }

    public function testPreparingQueryFullQuery()
    {
        $rqlString = RqlParser::rqlDecode("eq(email,aaa@gmail.com)&limit(10,15)&sort(-name)&select(name,age,email)");
        $query = new RqlQuery();
        $query->setQuery(new EqNode('email', 'aaa@gmail.com'));
        $query->setSelect(new AggregateSelectNode(['name', 'age', 'email']));
        $query->setLimit(new LimitNode(10, 15));
        $query->setSort(new SortNode(['name' => -1]));

        $this->assertEquals($query, $rqlString);
    }

    public function testGroupbyOnly()
    {
        $queryByString = RqlParser::rqlDecode("groupby(id)");
        $query = new RqlQuery();
        $query->setGroupBy(new GroupbyNode(['id']));
        $this->assertEquals($query, $queryByString);
    }

    public function testGroupbyWithQuery()
    {
        $queryByString = RqlParser::rqlDecode('and(eq(email,aaa@gmail.com),or(le(age,1\,4),ge(age,1\.8)),ne(name,q1$3))&groupby(id)');
        $query = new RqlQuery();
        $query->setQuery(new AndNode([
            new EqNode('email', 'aaa@gmail.com'),
            new OrNode([
                new LeNode('age', '1,4'),
                new GeNode('age', '1.8'),
            ]),
            new NeNode('name', 'q1$3'),
        ]));
        $query->setGroupBy(new GroupbyNode(['id']));
        $this->assertEquals($query, $queryByString);
    }

    public function testContainsOnly()
    {
        $queryByString = RqlParser::rqlDecode("contains(id,1v23)");
        $query = new RqlQuery();
        $query->setQuery(new ContainsNode("id", "1v23"));
        $this->assertEquals($query, $queryByString);
    }

    public function testMatchOnly()
    {
        $queryByString = RqlParser::rqlDecode("match(id,1v23)");
        $query = new RqlQuery();
        $query->setQuery(new ContainsNode("id", "1v23"));
        $this->assertEquals($query, $queryByString);
    }

    public function testSpecialCharsSuccess()
    {
        $this->object = new Query();
        $this->object->setQuery(new EqNode("name", "asd(asd)asd"));
        $stringRql = RqlParser::rqlEncode($this->object);
        $query = RqlParser::rqlDecode($stringRql);
        $this->assertEquals($this->object->getQuery(), $query->getQuery());
    }

    public function testArrayNode()
    {
        $values = [];

        foreach (range(0, 100) as $index) {
            $values[] = "asd(a{$index}d)asd";
            $this->object = new Query();
            $this->object->setQuery(new OutNode("name", $values));
            $stringRql = RqlParser::rqlEncode($this->object);
            $query = RqlParser::rqlDecode($stringRql);
            $this->assertEquals($this->object->getQuery(), $query->getQuery());
        }
    }

    // ========== TYPE-HINTING TESTS ==========

    /**
     * Data provider for basic eq() type-hinting tests
     * Format: [rqlString, expectedType, expectedValue, description]
     */
    public function eqTypeHintingDataProvider(): array
    {
        return [
            // Integer auto-type
            ['eq(id,108693)', 'integer', 108693, 'integer without prefix'],
            ['eq(id,integer:42)', 'integer', 42, 'explicit integer prefix'],
            ['eq(count,0)', 'integer', 0, 'zero as integer'],
            ['eq(balance,-500)', 'integer', -500, 'negative integer'],
            ['eq(id,999999999)', 'integer', 999999999, 'large integer'],

            // String explicit type
            ['eq(id,string:108693)', 'string', '108693', 'explicit string prefix'],
            ['eq(code,string:1abc)', 'string', '1abc', 'string with non-numeric chars'],

            // Leading zeros behavior - parser treats them as octal (float in PHP 8)
            ['eq(id,01)', 'double', 1.0, 'leading zero treated as octal/float'],
            ['eq(id,string:01)', 'string', '01', 'leading zero preserved as string'],
            ['eq(id,00042)', 'double', 42.0, 'multiple leading zeros as float'],
            ['eq(id,string:00042)', 'string', '00042', 'multiple leading zeros as string'],
            ['eq(id,0108693)', 'double', 108693.0, 'number-like string with leading zero becomes float'],
            ['eq(id,string:0108693)', 'string', '0108693', 'number-like string with prefix stays string'],
            ['eq(id,integer:007)', 'integer', 7, 'explicit integer prefix strips zeros'],

            // Float type
            ['eq(price,1.0)', 'double', 1.0, 'float literal 1.0'],
            ['eq(price,float:1)', 'double', 1.0, 'explicit float prefix'],
            ['eq(rate,1.5)', 'double', 1.5, 'float value'],
            ['eq(amount,0.0)', 'double', 0.0, 'zero as float'],
            ['eq(temperature,-15.5)', 'double', -15.5, 'negative float'],
            ['eq(price,123.456789)', 'double', 123.456789, 'float with many decimals'],

            // Boolean type
            ['eq(active,true())', 'boolean', true, 'true() function'],
            ['eq(active,boolean:1)', 'boolean', true, 'boolean:1 prefix'],
            ['eq(active,false())', 'boolean', false, 'false() function'],
            ['eq(active,boolean:0)', 'boolean', false, 'boolean:0 prefix'],
        ];
    }

    /**
     * @dataProvider eqTypeHintingDataProvider
     * Test eq() operator with various type hints
     */
    public function testEqTypeHinting(string $rqlString, string $expectedType, $expectedValue, string $description): void
    {
        $query = RqlParser::rqlDecode($rqlString);
        /** @var EqNode $node */
        $node = $query->getQuery();

        $this->assertInstanceOf(EqNode::class, $node, "Failed for: {$description}");
        $this->assertSame($expectedType, gettype($node->getValue()), "Type mismatch for: {$description}");
        $this->assertSame($expectedValue, $node->getValue(), "Value mismatch for: {$description}");
    }

    /**
     * Data provider for comparison operators type-hinting
     * Format: [operator, rqlString, nodeClass, expectedType, expectedValue]
     */
    public function comparisonOperatorsDataProvider(): array
    {
        return [
            ['lt', 'lt(age,10)', LtNode::class, 'integer', 10],
            ['lt', 'lt(price,9.99)', LtNode::class, 'double', 9.99],
            ['gt', 'gt(rate,1.5)', GtNode::class, 'double', 1.5],
            ['gt', 'gt(count,100)', GtNode::class, 'integer', 100],
            ['le', 'le(code,string:100)', LeNode::class, 'string', '100'],
            ['le', 'le(score,50)', LeNode::class, 'integer', 50],
            ['ge', 'ge(flag,boolean:0)', GeNode::class, 'boolean', false],
            ['ge', 'ge(amount,0.0)', GeNode::class, 'double', 0.0],
            ['ne', 'ne(status,0)', NeNode::class, 'integer', 0],
            ['ne', 'ne(name,string:test)', NeNode::class, 'string', 'test'],
        ];
    }

    /**
     * @dataProvider comparisonOperatorsDataProvider
     * Test comparison operators with type-hinting
     */
    public function testComparisonOperatorsTypeHinting(
        string $operator,
        string $rqlString,
        string $nodeClass,
        string $expectedType,
        $expectedValue
    ): void {
        $query = RqlParser::rqlDecode($rqlString);
        $node = $query->getQuery();

        $this->assertInstanceOf($nodeClass, $node, "Failed for operator: {$operator}");
        $this->assertSame($expectedType, gettype($node->getValue()), "Type mismatch for: {$operator}");
        $this->assertSame($expectedValue, $node->getValue(), "Value mismatch for: {$operator}");
    }

    /**
     * Data provider for array operators (in/out) with mixed types
     * Format: [rqlString, nodeClass, field, expectedValues]
     */
    public function arrayOperatorsDataProvider(): array
    {
        return [
            'in with mixed types' => [
                'in(tag,(2,float:3,string:004,boolean:1))',
                InNode::class,
                'tag',
                [2, 3.0, '004', true], // int, float, string, bool
            ],
            'out with mixed types' => [
                'out(code,(1,string:02,3))',
                OutNode::class,
                'code',
                [1, '02', 3], // int, string, int
            ],
            'in with all boolean variants' => [
                'in(flags,(true(),false(),boolean:1,boolean:0))',
                InNode::class,
                'flags',
                [true, false, true, false],
            ],
        ];
    }

    /**
     * @dataProvider arrayOperatorsDataProvider
     * Test array operators (in/out) with mixed type-hinting
     */
    public function testArrayOperatorsTypeHinting(
        string $rqlString,
        string $nodeClass,
        string $expectedField,
        array $expectedValues
    ): void {
        $query = RqlParser::rqlDecode($rqlString);
        $node = $query->getQuery();

        $this->assertInstanceOf($nodeClass, $node);
        $this->assertSame($expectedField, $node->getField());

        $values = $node->getValues();
        $this->assertCount(count($expectedValues), $values);

        foreach ($expectedValues as $i => $expectedValue) {
            $this->assertSame(gettype($expectedValue), gettype($values[$i]), "Type mismatch at position {$i}");
            $this->assertSame($expectedValue, $values[$i], "Value mismatch at position {$i}");
        }
    }

    /**
     * Data provider for edge cases in type-hinting
     * Format: [rqlQueries, expectedTypes, expectedValues, shouldBeEquivalent]
     */
    public function edgeCasesDataProvider(): array
    {
        return [
            'type difference: 1 vs 1.0 vs float:1' => [
                ['eq(v,1)', 'eq(v,1.0)', 'eq(v,float:1)'],
                ['integer', 'double', 'double'],
                [1, 1.0, 1.0],
                false, // different types, not strictly equivalent
            ],
            'boolean equivalence: true() vs boolean:1' => [
                ['eq(f,true())', 'eq(f,boolean:1)'],
                ['boolean', 'boolean'],
                [true, true],
                true, // same type and value
            ],
            'boolean equivalence: false() vs boolean:0' => [
                ['eq(f,false())', 'eq(f,boolean:0)'],
                ['boolean', 'boolean'],
                [false, false],
                true, // same type and value
            ],
        ];
    }

    /**
     * @dataProvider edgeCasesDataProvider
     * Test edge cases and equivalence between different type-hinting forms
     */
    public function testTypeHintingEdgeCases(
        array $rqlQueries,
        array $expectedTypes,
        array $expectedValues,
        bool $shouldBeEquivalent
    ): void {
        foreach ($rqlQueries as $i => $rqlQuery) {
            $node = RqlParser::rqlDecode($rqlQuery)->getQuery();

            $this->assertSame(
                $expectedTypes[$i],
                gettype($node->getValue()),
                "Type mismatch for query: {$rqlQuery}"
            );
            $this->assertSame(
                $expectedValues[$i],
                $node->getValue(),
                "Value mismatch for query: {$rqlQuery}"
            );
        }

        // Check equivalence if expected
        if ($shouldBeEquivalent) {
            $firstValue = RqlParser::rqlDecode($rqlQueries[0])->getQuery()->getValue();
            foreach (array_slice($rqlQueries, 1) as $rqlQuery) {
                $value = RqlParser::rqlDecode($rqlQuery)->getQuery()->getValue();
                $this->assertSame($firstValue, $value, "Values not equivalent for: {$rqlQuery}");
            }
        }
    }
}
