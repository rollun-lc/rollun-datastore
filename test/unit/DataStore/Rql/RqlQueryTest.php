<?php

namespace rollun\test\unit\DataStore\Rql;

use PHPUnit\Framework\TestCase;
use rollun\datastore\Rql\Node\BinaryNode\EqtNode;
use rollun\datastore\Rql\Node\GroupByNode;
use rollun\datastore\Rql\RqlQuery;
use Xiag\Rql\Parser\Node\LimitNode;
use Xiag\Rql\Parser\Node\SelectNode;
use Xiag\Rql\Parser\Node\SortNode;

class RqlQueryTest extends TestCase
{
    public function testGroupByGetterAndSetterNode()
    {
        $groupByNode = new GroupByNode([]);
        $object = new RqlQuery();
        $object->setGroupBy($groupByNode);
        $this->assertAttributeEquals($groupByNode, 'groupBy', $object);
    }

    public function testConstructWithRqlQuery()
    {
        $rqlQuery = new RqlQuery();
        $rqlQuery->setQuery(new EqtNode('a'));
        $rqlQuery->setSelect(new SelectNode());
        $rqlQuery->setLimit(new LimitNode('a'));
        $rqlQuery->setSort(new SortNode());
        $rqlQuery->setGroupBy(new GroupByNode([]));

        $this->assertEquals(new RqlQuery($rqlQuery), $rqlQuery);
    }
}
