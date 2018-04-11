<?php

namespace rollun\test\datastore\DataStore\Aggregate;

use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\Rql\Node\AggregateFunctionNode;
use rollun\datastore\Rql\Node\AggregateSelectNode;
use rollun\datastore\Rql\Node\GroupbyNode;
use rollun\datastore\Rql\RqlQuery;
use rollun\test\datastore\DataStore\AbstractDataStoreTest;
use Xiag\Rql\Parser\Node\LimitNode;
use Xiag\Rql\Parser\Node\SelectNode;
use Xiag\Rql\Parser\Query;

/**
 * Class AbstractAggregateTest
 * @package rollun\test\datastore\DataStore\Aggregate
 */
abstract class AbstractAggregateTest extends AbstractDataStoreTest
{
    /**
     * DataProvider for testMaxSuccess
     * @return array
     */
    abstract function provideMaxSuccessData();

    /**
     * DataProvider for testMinSuccess
     * @return array
     */
    abstract function provideMinSuccessData();

    /**
     * DataProvider for testAvgSuccess
     * @return array
     */
    abstract function provideAvgSuccessData();

    /**
     * DataProvider for testCountSuccess
     * @return array
     */
    abstract function provideCountSuccessData();

    /**
     * DataProvider for testAggregateWithLimitOffsetSuccess
     * @return array
     */
    abstract function provideAggregateWithLimitOffsetSuccessData();

    /**
     * DataProvider for testAggregateWithGroupBySuccess
     * @return array
     */
    abstract function provideAggregateWithGroupBySuccessData();

    /**
     * DataProvider for testAggregateWithSelectGroupBySuccess
     * @return array
     */
    abstract function provideAggregateWithSelectGroupBySuccessData();

    /**
     * DataProvider for testAggregateWithLimitOffsetGroupBySuccess
     * @return array
     */
    abstract function provideAggregateWithLimitOffsetGroupBySuccessData();

    /**
     * DataProvider for testAggregateWithGroupByException
     * @return array
     */
    abstract function provideAggregateWithGroupByExceptionData();

    /**
     * DataProvider for testAggregateWithSelectException
     * @return array
     */
    abstract function provideAggregateWithSelectExceptionData();


    /**
     * @param $fieldName
     * @param $expectedResult
     * @dataProvider provideMaxSuccessData
     */
    public function testMaxSuccess($fieldName, $expectedResult)
    {
        $query = new Query();
        $query->setSelect(new SelectNode([
            new AggregateFunctionNode("max", $fieldName)
        ]));
        $result = $this->object->query($query);
        $this->assertEquals($expectedResult, $result);
        //$this->assertSame($expectedResult, $result);
    }

    /**
     * @param $fieldName
     * @param $expectedResult
     * @dataProvider provideMinSuccessData
     */
    public function testMinSuccess($fieldName, $expectedResult)
    {
        $query = new Query();
        $query->setSelect(new SelectNode([
            new AggregateFunctionNode("min", $fieldName)
        ]));
        $result = $this->object->query($query);
        $this->assertEquals($expectedResult, $result);
        //$this->assertSame($expectedResult, $result);
    }

    /**
     * @param $fieldName
     * @param $expectedResult
     * @dataProvider provideAvgSuccessData
     */
    public function testAvgSuccess($fieldName, $expectedResult)
    {
        $query = new Query();
        $query->setSelect(new SelectNode([
            new AggregateFunctionNode("avg", $fieldName)
        ]));
        $result = $this->object->query($query);
        $this->assertEquals($expectedResult, $result);
        //$this->assertSame($expectedResult, $result);
    }

    /**
     * @param $fieldName
     * @param $expectedResult
     * @dataProvider provideCountSuccessData
     */
    public function testCountSuccess($fieldName, $expectedResult)
    {
        $query = new Query();
        $query->setSelect(new SelectNode([
            new AggregateFunctionNode("count", $fieldName)
        ]));
        $result = $this->object->query($query);
        $this->assertEquals($expectedResult, $result);
        //$this->assertSame($expectedResult, $result);
    }

    /**
     * @param LimitNode $limitNode
     * @param AggregateFunctionNode $aggregateNode
     * @param $expectedResult
     * @dataProvider provideAggregateWithLimitOffsetSuccessData
     */
    public function testAggregateWithLimitOffsetSuccess(LimitNode $limitNode, AggregateFunctionNode $aggregateNode, $expectedResult) {
        $query = new Query();
        $query->setSelect(new SelectNode([
            $aggregateNode
        ]));
        $query->setLimit($limitNode);
        $result = $this->object->query($query);
        $this->assertLessThanOrEqual($limitNode->getLimit(), count($expectedResult), "Expected result is not valid.");
        $this->assertLessThanOrEqual($limitNode->getLimit(), count($result));
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @param AggregateFunctionNode $aggregateNode
     * @param GroupbyNode $groupByNode
     * @param $expectedResult
     * @dataProvider provideAggregateWithGroupBySuccessData
     */
    public function testAggregateWithGroupBySuccess(AggregateFunctionNode $aggregateNode, GroupbyNode $groupByNode, $expectedResult) {
        $query = new RqlQuery();
        $query->setSelect(new SelectNode([
            $aggregateNode,
        ]));
        $query->setGroupby($groupByNode);
        $result = $this->object->query($query);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @param AggregateFunctionNode $aggregateNode
     * @param GroupbyNode $groupByNode
     * @param $expectedResult
     * @dataProvider provideAggregateWithSelectGroupBySuccessData
     */
    public function testAggregateWithSelectGroupBySuccess(AggregateFunctionNode $aggregateNode, GroupbyNode $groupByNode, $expectedResult) {
        $query = new RqlQuery();
        $query->setSelect(new SelectNode(array_merge([
            $aggregateNode,
        ], $groupByNode->getFields())));
        $query->setGroupby($groupByNode);
        $result = $this->object->query($query);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @param LimitNode $limitNode
     * @param AggregateFunctionNode $aggregateNode
     * @param GroupbyNode $groupByNode
     * @param $expectedResult
     * @dataProvider provideAggregateWithLimitOffsetGroupBySuccessData
     */
    public function testAggregateWithLimitOffsetGroupBySuccess(LimitNode $limitNode, AggregateFunctionNode $aggregateNode, GroupbyNode $groupByNode, $expectedResult) {
        $query = new RqlQuery();
        $query->setSelect(new SelectNode([
            $aggregateNode
        ]));
        $query->setLimit($limitNode);
        $query->setGroupby($groupByNode);
        $result = $this->object->query($query);
        $this->assertLessThanOrEqual($limitNode->getLimit(), count($expectedResult), "Expected result is not valid.");
        $this->assertLessThanOrEqual($limitNode->getLimit(), count($result));
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @param AggregateFunctionNode $aggregateNode
     * @param GroupbyNode $groupByNode
     * @expectedException DataStoreException
     * @dataProvider provideAggregateWithGroupByExceptionData
     */
    public function testAggregateWithGroupByException(AggregateFunctionNode $aggregateNode, GroupbyNode $groupByNode) {
        $query = new RqlQuery();
        $query->setSelect(new SelectNode([$aggregateNode]));
        $query->setGroupby($groupByNode);
        $this->object->query($query);
    }

    /**
     * @param AggregateFunctionNode $aggregateNode
     * @expectedException DataStoreException
     * @dataProvider provideAggregateWithSelectExceptionData
     */
    public function testAggregateWithSelectException(AggregateFunctionNode $aggregateNode) {
        $query = new Query();
        $query->setSelect(new SelectNode([$aggregateNode]));
        $this->object->query($query);
    }
}