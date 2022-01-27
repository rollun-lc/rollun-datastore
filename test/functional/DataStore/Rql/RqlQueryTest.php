<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\functional\DataStore\Rql;

use PHPUnit\Framework\TestCase;
use rollun\datastore\Rql\Node\BinaryNode\EqtNode;
use rollun\datastore\Rql\RqlQuery;
use Graviton\RqlParser\Parser\Node\LimitNode;
use Graviton\RqlParser\Parser\Node\SelectNode;
use Graviton\RqlParser\Parser\Node\SortNode;
use Graviton\RqlParser\Parser\Query;

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
