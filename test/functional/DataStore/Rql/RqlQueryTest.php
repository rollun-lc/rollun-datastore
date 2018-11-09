<?php

namespace test\functional\DataStore\Rql;

use PHPUnit\Framework\TestCase;
use rollun\datastore\Rql\Node\BinaryNode\EqtNode;
use rollun\datastore\Rql\RqlQuery;
use Xiag\Rql\Parser\Node\LimitNode;
use Xiag\Rql\Parser\Node\SelectNode;
use Xiag\Rql\Parser\Node\SortNode;
use Xiag\Rql\Parser\Query;

class RqlQueryTest extends TestCase
{
    public function testConstructWithQuery()
    {
        $query = new Query();
        $query->setQuery(new EqtNode('a'));
        $query->setSelect(new SelectNode());
        $query->setLimit(new LimitNode('a'));
        $query->setSort(new SortNode());

        $rqlQuery = new RqlQuery();
        $rqlQuery->setQuery(new EqtNode('a'));
        $rqlQuery->setSelect(new SelectNode());
        $rqlQuery->setLimit(new LimitNode('a'));
        $rqlQuery->setSort(new SortNode());

        $this->assertEquals(new RqlQuery($query), $rqlQuery);
    }
}
