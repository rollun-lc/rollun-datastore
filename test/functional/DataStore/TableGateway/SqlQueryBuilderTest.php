<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\functional\DataStore\TableGateway;

use PHPUnit\Framework\TestCase;
use rollun\datastore\Rql\Node\AggregateFunctionNode;
use rollun\datastore\Rql\Node\AlikeGlobNode;
use rollun\datastore\Rql\Node\AlikeNode;
use rollun\datastore\Rql\Node\BinaryNode\EqfNode;
use rollun\datastore\Rql\Node\BinaryNode\EqnNode;
use rollun\datastore\Rql\Node\BinaryNode\EqtNode;
use rollun\datastore\Rql\Node\BinaryNode\IeNode;
use rollun\datastore\Rql\Node\ContainsNode;
use rollun\datastore\Rql\Node\GroupbyNode;
use rollun\datastore\Rql\Node\LikeGlobNode;
use rollun\datastore\Rql\RqlQuery;
use rollun\datastore\TableGateway\SqlQueryBuilder;
use Xiag\Rql\Parser\Node\LimitNode;
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
use Xiag\Rql\Parser\Node\SelectNode;
use Xiag\Rql\Parser\Node\SortNode;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Adapter\Driver\ConnectionInterface;
use Zend\Db\Adapter\Driver\DriverInterface;
use Zend\Db\Adapter\Driver\ResultInterface;
use Zend\Db\Adapter\Driver\StatementInterface;
use Zend\Db\Adapter\Platform\PlatformInterface;

class SqlQueryBuilderTest extends TestCase
{
    /**
     * @var Adapter
     */
    protected $mockAdapter;

    public function setUp()
    {
        $mockResult = $this->getMockBuilder(ResultInterface::class)
            ->getMock();
        $mockStatement = $this->getMockBuilder(StatementInterface::class)
            ->getMock();
        $mockStatement->expects($this->any())
            ->method('execute')
            ->will($this->returnValue($mockResult));
        $mockConnection = $this->getMockBuilder(ConnectionInterface::class)
            ->getMock();
        $mockDriver = $this->getMockBuilder(DriverInterface::class)
            ->getMock();
        $mockDriver->expects($this->any())
            ->method('createStatement')
            ->will($this->returnValue($mockStatement));
        $mockDriver->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($mockConnection));
        $mockPlatform = $this->getPlatformMockObject();

        // Setup mock adapter
        $this->mockAdapter = $this->getMockBuilder(Adapter::class)
            ->setMethods()
            ->setConstructorArgs([$mockDriver, $mockPlatform])
            ->getMock();
    }

    public function testConstruct()
    {
        $tableName = 'foo';
        $object = new SqlQueryBuilder($this->mockAdapter, $tableName);

        $this->assertAttributeEquals($tableName, 'tableName', $object);
    }

    public function testBuildSqlWithBinaryNodes()
    {
        $tableName = 'table';
        $object = new SqlQueryBuilder($this->mockAdapter, $tableName);

        $rqlQuery = new RqlQuery();
        $rqlQuery->setQuery(new AndNode([
            new IeNode('a'),
            new EqtNode('a'),
            new EqfNode('a'),
            new EqnNode('a'),
        ]));

        $select = "SELECT `table`.* FROM `table` ";
        $where = "WHERE ((`a` IS NULL OR `a` IS FALSE OR `a` = '') AND (`a` IS TRUE) AND (`a` IS FALSE) AND (`a` IS NULL))";

        $this->assertEquals($select.$where, $object->buildSql($rqlQuery));
    }

    public function testBuildSqlWithScalarNodes()
    {
        $tableName = 'table';
        $object = new SqlQueryBuilder($this->mockAdapter, $tableName);

        $rqlQuery = new RqlQuery();
        $rqlQuery->setQuery(new AndNode([
            new EqNode('a', 'b'),
            new GeNode('a', 'b'),
            new GtNode('a', 'b'),
            new LeNode('a', 'b'),
            new LtNode('a', 'b'),
            new NeNode('a', 'b'),
        ]));

        $select = "SELECT `table`.* FROM `table` ";
        $where = "WHERE ((`a`='b') AND (`a`>='b') AND (`a`>'b') AND (`a`<='b') AND (`a`<'b') AND (`a`<>'b'))";

        $this->assertEquals($select.$where, $object->buildSql($rqlQuery));
    }

    public function testBuildSqlWithLikeNodes()
    {
        $tableName = 'table';
        $object = new SqlQueryBuilder($this->mockAdapter, $tableName);

        $rqlQuery = new RqlQuery();
        $rqlQuery->setQuery(new AndNode([
            new LikeNode('a', 'b'),
            new LikeGlobNode('a', 'b'),
            new AlikeNode('a', 'b'),
            new ALikeGlobNode('a', 'b'),
            new ContainsNode('a', 'b'),
        ]));

        $select = "SELECT `table`.* FROM `table` ";
        $where = "WHERE ((`a` LIKE BINARY 'b') AND (`a` LIKE BINARY 'b') "
            ."AND (`a` LIKE 'b') AND (`a` LIKE 'b') AND (`a` LIKE '%b%'))";

        $this->assertEquals($select.$where, $object->buildSql($rqlQuery));
    }

    public function testBuildSqlWithNotNode()
    {
        $tableName = 'table';
        $object = new SqlQueryBuilder($this->mockAdapter, $tableName);

        $rqlQuery = new RqlQuery();
        $rqlQuery->setQuery(new NotNode([
            new InNode('a', ['b', 'c']),
            new OutNode('a', ['b', 'c']),
        ]));

        $select = "SELECT `table`.* FROM `table` ";
        // TODO: it is misbehavior
        $where = "WHERE ( NOT ((`a` IN ('b','c')) error (`a` NOT IN ('b','c'))) )";

        $this->assertEquals($select.$where, $object->buildSql($rqlQuery));
    }

    public function testBuildSqlWithLimitNode()
    {
        $tableName = 'table';
        $object = new SqlQueryBuilder($this->mockAdapter, $tableName);

        $rqlQuery = new RqlQuery();
        $rqlQuery->setLimit(new LimitNode(5));

        $select = "SELECT `table`.* FROM `table` ";
        $where = "WHERE '1' = '1' ";
        $limit = "LIMIT 5";

        $this->assertEquals($select.$where.$limit, $object->buildSql($rqlQuery));
    }

    public function testBuildSqlWithSortNode()
    {
        $tableName = 'table';
        $object = new SqlQueryBuilder($this->mockAdapter, $tableName);

        $rqlQuery = new RqlQuery();
        $rqlQuery->setSort(new SortNode(['a' => SortNode::SORT_ASC]));

        $select = "SELECT `table`.* FROM `table` ";
        $where = "WHERE '1' = '1' ";
        $sort = "ORDER BY `a` ASC";

        $this->assertEquals($select.$where.$sort, $object->buildSql($rqlQuery));

        $rqlQuery->setSort(new SortNode([
            'a' => SortNode::SORT_ASC,
            'b' => SortNode::SORT_DESC,
        ]));
        $sort = "ORDER BY `a` ASC, `b` DESC";
        $this->assertEquals($select.$where.$sort, $object->buildSql($rqlQuery));
    }

    public function testBuildSqlWithGroupByNode()
    {
        $tableName = 'table';
        $object = new SqlQueryBuilder($this->mockAdapter, $tableName);

        $rqlQuery = new RqlQuery();
        $rqlQuery->setGroupBy(new GroupbyNode(['a', 'b']));

        $select = "SELECT `table`.* FROM `table` ";
        $where = "WHERE '1' = '1' ";
        $groupBy = "GROUP BY `a`, `b`";

        $this->assertEquals($select.$where.$groupBy, $object->buildSql($rqlQuery));
    }

    public function testBuildSqlWithSelectNode()
    {
        $tableName = 'table';
        $object = new SqlQueryBuilder($this->mockAdapter, $tableName);

        $rqlQuery = new RqlQuery();
        $rqlQuery->setSelect(new SelectNode([
            new AggregateFunctionNode('count', 'a'),
        ]));

        $sql = "SELECT count(a) AS `count(a)` FROM `table` WHERE '1' = '1'";
        $this->assertEquals($sql, $object->buildSql($rqlQuery));
    }

    public function testAllNodeType()
    {
        $tableName = 'table';
        $object = new SqlQueryBuilder($this->mockAdapter, $tableName);

        $rqlQuery = new RqlQuery();

        $rqlQuery->setQuery(new AndNode([
            new LikeNode('a', 'b'),
            new OrNode([
                new EqfNode('a'),
                new NotNode([new GtNode('a', 'b')]),
            ]),
        ]));

        $rqlQuery->setLimit(new LimitNode(5));
        $rqlQuery->setSort(new SortNode(['a' => SortNode::SORT_ASC]));
        $rqlQuery->setGroupBy(new GroupbyNode(['a', 'b']));

        $select = "SELECT `table`.* FROM `table` ";
        $where = "WHERE ((`a` LIKE BINARY 'b') AND ((`a` IS FALSE) OR ( NOT ((`a`>'b')) ))) ";
        $groupBy = "GROUP BY `a`, `b` ";
        $sort = "ORDER BY `a` ASC ";
        $limit = "LIMIT 5";

        $this->assertEquals($select.$where.$groupBy.$sort.$limit, $object->buildSql($rqlQuery));
    }

    protected function getPlatformMockObject()
    {
        $platform = $this->getMockBuilder(PlatformInterface::class)
            ->getMock();

        $platform->expects($this->any())
            ->method('quoteValue')
            ->will($this->returnCallback(function ($argument) {
                return "'{$argument}'";
            }));

        $platform->expects($this->any())
            ->method('quoteIdentifierInFragment')
            ->will($this->returnCallback(function ($argument) {
                return "`{$argument}`";
            }));

        $platform->expects($this->any())
            ->method('quoteIdentifier')
            ->will($this->returnCallback(function ($argument) {
                return "`{$argument}`";
            }));

        $platform->expects($this->any())
            ->method('getIdentifierSeparator')
            ->will($this->returnValue('.'));


        $platform->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('MySQL'));

        return $platform;
    }
}
