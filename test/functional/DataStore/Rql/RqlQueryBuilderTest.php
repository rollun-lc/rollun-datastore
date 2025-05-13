<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\functional\DataStore\Rql;

use PHPUnit\Framework\TestCase;
use rollun\datastore\Rql\Node;
use rollun\datastore\Rql\RqlQuery;
use rollun\datastore\Rql\RqlQueryBuilder;
use Xiag\Rql\Parser\Node\Query\LogicOperator\AndNode;

class RqlQueryBuilderTest extends TestCase
{
    /**
     * Test only new added nodes
     */
    public function testAddNodes()
    {
        $queryNodes = [
            new Node\BinaryNode\EqtNode('a'),
            new Node\BinaryNode\EqfNode('a'),
            new Node\BinaryNode\EqnNode('a'),
            new Node\BinaryNode\IeNode('a'),
            new Node\AggregateFunctionNode('a', 'b'),
            new Node\AlikeGlobNode('a', 'b'),
            new Node\AlikeNode('a', 'b'),
            new Node\ContainsNode('a', 'b'),
            new Node\LikeGlobNode('a', 'b'),
        ];
        $groupByNode = new Node\GroupbyNode([]);
        $selectNode = new Node\AggregateSelectNode();
        $allNodes = array_merge($queryNodes, [$groupByNode, $selectNode]);

        $rqlQuery = new RqlQuery();
        $rqlQuery->setQuery(new AndNode($queryNodes));
        $rqlQuery->setGroupBy(new Node\GroupbyNode([]));
        $rqlQuery->setSelect(new Node\AggregateSelectNode([]));

        $object = new RqlQueryBuilder();

        foreach ($allNodes as $node) {
            $object->addNode($node);
        }

        $this->assertEquals($rqlQuery, $object->getQuery());
    }
}
