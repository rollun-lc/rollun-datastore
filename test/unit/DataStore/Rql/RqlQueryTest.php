<?php

namespace rollun\test\unit\DataStore\Rql;

use Graviton\RqlParser\Node\LimitNode;
use Graviton\RqlParser\Node\SelectNode;
use Graviton\RqlParser\Node\SortNode;
use PHPUnit\Framework\TestCase;
use rollun\datastore\Rql\Node\BinaryNode\EqtNode;
use rollun\datastore\Rql\Node\GroupbyNode;
use rollun\datastore\Rql\RqlQuery;

class RqlQueryTest extends TestCase
{
    public function testGroupByGetterAndSetterNode()
    {
        $groupByNode = new GroupbyNode([]);
        $object = new RqlQuery();
        $object->setGroupBy($groupByNode);
        //$this->assertAttributeEquals($groupByNode, 'groupBy', $object);
        $reflection = new \ReflectionProperty($object, 'groupBy');
        $reflection->setAccessible(true);
        $this->assertEquals($groupByNode, $reflection->getValue($object));
    }

    public function testConstructWithRqlQuery()
    {
        $rqlQuery = new RqlQuery();
        $rqlQuery->setQuery(new EqtNode('a'));
        $rqlQuery->setSelect(new SelectNode());
        $rqlQuery->setLimit(new LimitNode('a'));
        $rqlQuery->setSort(new SortNode());
        $rqlQuery->setGroupBy(new GroupbyNode([]));

        $this->assertEquals(new RqlQuery($rqlQuery), $rqlQuery);
    }
}
