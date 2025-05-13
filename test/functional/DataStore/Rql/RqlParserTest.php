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
}
