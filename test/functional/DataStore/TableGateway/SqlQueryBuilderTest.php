<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace functional\DataStore\TableGateway;

use PHPUnit\Framework\TestCase;
use rollun\datastore\Rql\Node\BinaryNode\EqfNode;
use rollun\datastore\Rql\Node\BinaryNode\EqnNode;
use rollun\datastore\Rql\Node\BinaryNode\EqtNode;
use rollun\datastore\Rql\Node\GroupByNode;
use rollun\datastore\Rql\RqlQuery;
use rollun\datastore\TableGateway\SqlQueryBuilder;
use Xiag\Rql\Parser\Node\LimitNode;
use Xiag\Rql\Parser\Node\Query\LogicOperator\AndNode;
use Xiag\Rql\Parser\Node\Query\LogicOperator\OrNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\GtNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\LtNode;
use Xiag\Rql\Parser\Node\SortNode;
use Zend\Db\Sql\Platform\AbstractPlatform;
use Zend\Db\Sql\Platform\Mysql\Mysql;

class SqlQueryBuilderTest extends TestCase
{
    public function createObject($tableName, AbstractPlatform $platform = null, $mysqlConditionBuilder = null)
    {
        return new SqlQueryBuilder(
            isset($platform) ? $platform : new Mysql(),
            $tableName,
            $mysqlConditionBuilder
        );
    }

    public function testCreateSql()
    {
        \PHPUnit_Framework_Error_Notice::$enabled = false;
        $rqlQuery = new RqlQuery();
        $rqlQuery->setQuery(new OrNode([
            new EqtNode('a'),
            new EqfNode('a'),
            new EqnNode('a'),
            new AndNode([
                new LtNode('a', 'b'),
                new GtNode('a', 'b'),
            ])
        ]));
        $rqlQuery->setLimit(new LimitNode(5));
        $rqlQuery->setGroupBy(new GroupByNode([]));
        $rqlQuery->setSort(new SortNode());

        $object = $this->createObject('table');
        $sql = $object->buildSql($rqlQuery);

        $expectedSql = 'SELECT `table`.* FROM `table` ' .
            'WHERE ((`a` is TRUE) OR (`a` is FALSE) OR (`a` is NULL) OR ((`a`<\'b\') AND (`a`>\'b\'))) LIMIT 5';

        $this->assertEquals($expectedSql, $sql);
    }
}
