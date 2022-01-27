<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\old\RqlParser;

use PHPUnit\Framework\TestCase;
use Graviton\RqlParser\Parser\Node\LimitNode;
use Graviton\RqlParser\Parser\Node\Query\ScalarOperator\EqNode;
use Graviton\RqlParser\Parser\Node\SortNode;
use Graviton\RqlParser\Parser\Query;
use rollun\datastore\Rql\Node\AggregateSelectNode;
use rollun\datastore\Rql\RqlQuery;

class RqlQueryTest extends TestCase
{
    /** @var  RqlQuery */
    private $object;

    public function testQueryNode()
    {
        $this->object = new RqlQuery("eq(a,1)");

        $query = new Query();
        $query->setQuery(
                new EqNode("a", "1")
        );
        $this->assertEquals($query->getQuery(), $this->object->getQuery());
    }

    public function testSortNode()
    {
        $this->object = new RqlQuery("sort(-a,+b)");

        $query = new Query();
        $query->setSort(new SortNode(['a' => -1, 'b' => 1]));
        $this->assertEquals($query->getSort(), $this->object->getSort());
    }

    public function testSelectNode()
    {
        $this->object = new RqlQuery("select(sadf_q,ads_x)");

        $query = new Query();
        $query->setSelect(new AggregateSelectNode(['sadf_q', 'ads_x']));
        $this->assertEquals($query->getSelect(), $this->object->getSelect());
    }

    public function testLimitNode()
    {
        $this->object = new RqlQuery("limit(10,13)");

        $query = new Query();
        $query->setLimit(new LimitNode(10, 13));
        $this->assertEquals($query->getLimit(), $this->object->getLimit());
    }

    public function testLimitNodeDefaultOffset()
    {
        $this->object = new RqlQuery("limit(10)");

        $query = new Query();
        $query->setLimit(new LimitNode(10, 0));
        $this->assertEquals($query->getLimit(), $this->object->getLimit());
    }
}
