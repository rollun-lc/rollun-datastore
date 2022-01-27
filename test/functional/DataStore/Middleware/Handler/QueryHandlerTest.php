<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\functional\DataStore\Middleware\Handler;

use PHPUnit_Framework_MockObject_MockObject;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use rollun\datastore\DataStore\Interfaces\ReadInterface;
use rollun\datastore\Middleware\Handler\QueryHandler;
use rollun\datastore\Rql\Node\AggregateFunctionNode;
use rollun\datastore\Rql\RqlQuery;
use Graviton\RqlParser\Parser\Node\LimitNode;
use Graviton\RqlParser\Parser\Node\SelectNode;
use Zend\Diactoros\ServerRequest;

class QueryHandlerTest extends BaseHandlerTest
{
    protected function createObject(DataStoresInterface $dataStore = null)
    {
        return new QueryHandler(is_null($dataStore) ? $this->createDataStoreEmptyMock() : $dataStore);
    }

    public function methodProvider()
    {
        return [
            ['POST'],
            // ['GET'],
            ['DELETE'],
            ['PUT'],
            ['PATCH'],
        ];
    }

    public function testCanHandleSuccess()
    {
        $request = new ServerRequest();
        $request = $request->withMethod('GET');
        $request = $request->withAttribute('rqlQueryObject', new RqlQuery('eq(a,1)'));
        $request = $request->withAttribute('primaryKeyValue', null);

        $object = $this->createObject();
        $this->assertTrue($object->canHandle($request));
    }

    /**
     * @dataProvider methodProvider
     * @param $notValidMethod
     */
    public function testCanHandleFailCauseMethod($notValidMethod)
    {
        $request = new ServerRequest();
        $request = $request->withMethod($notValidMethod);
        $request = $request->withAttribute('rqlQueryObject', new RqlQuery('eq(a,1)'));
        $request = $request->withAttribute('primaryKeyValue', null);

        $object = $this->createObject();
        $this->assertFalse($object->canHandle($request));
    }

    public function testCanHandleFailCauseRqlQueryNotEmpty()
    {
        $request = new ServerRequest();
        $request = $request->withMethod('GET');
        $request = $request->withAttribute('primaryKeyValue', null);
        $request = $request->withAttribute('rqlQueryObject', null);

        $object = $this->createObject();
        $this->assertFalse($object->canHandle($request));
    }

    public function testCanHandleCausePrimaryKey()
    {
        $request = new ServerRequest();
        $request = $request->withMethod('GET');
        $request = $request->withAttribute('rqlQueryObject', new RqlQuery('eq(a,1)'));
        $request = $request->withAttribute('primaryKeyValue', 1);

        $object = $this->createObject();
        $this->assertFalse($object->canHandle($request));
    }

    public function queryDataProvider()
    {
        return [
            [new RqlQuery('eq(a,1)')],
            [new RqlQuery('eq(a,1)&limit(5)')],
            [new RqlQuery('eq(a,1)&limit(5,10)')],
        ];
    }

    /**
     * @dataProvider queryDataProvider
     * @param RqlQuery $rqlQuery
     */
    public function testProcessSuccess(RqlQuery $rqlQuery)
    {
        $item = [
            'id' => 1,
            'name' => 'name',
        ];

        $request = new ServerRequest();
        $request = $request->withMethod('GET');
        $request = $request->withAttribute('rqlQueryObject', $rqlQuery);
        $request = $request->withAttribute('primaryKeyValue', null);
        $request = $request->withAttribute('withContentRange', true);

        $dataStore = $this->createDataStoreEmptyMock();

        $dataStore->expects($this->at(0))
            ->method('query')
            ->with($rqlQuery)
            ->willReturn([$item]);

        $contentRange = $this->getContentRange(clone $rqlQuery, $dataStore, [$item]);
        $response = $this->createResponse(
            200,
            ['Content-Range' => $contentRange],
            [$item]
        );

        $object = $this->createObject($dataStore);
        $this->assertDelegateCallWithAssertionCallback($this->getAssertionCallback($response), $request, $object);
    }

    /**
     * @dataProvider queryDataProvider
     * @param RqlQuery $rqlQuery
     */
    public function testProcessSuccessWithOutContentRange(RqlQuery $rqlQuery)
    {
        $item = [
            'id' => 1,
            'name' => 'name',
        ];

        $request = new ServerRequest();
        $request = $request->withMethod('GET');
        $request = $request->withAttribute('rqlQueryObject', $rqlQuery);
        $request = $request->withAttribute('primaryKeyValue', null);
        $request = $request->withAttribute('withContentRange', false);

        $dataStore = $this->createDataStoreEmptyMock();

        $dataStore->expects($this->at(0))
            ->method('query')
            ->with($rqlQuery)
            ->willReturn([$item]);

        $response = $this->createResponse(200, [], [$item]);

        $object = $this->createObject($dataStore);
        $this->assertDelegateCallWithAssertionCallback($this->getAssertionCallback($response), $request, $object);
    }

    /**
     * @param RqlQuery $rqlQuery
     * @param DataStoresInterface|PHPUnit_Framework_MockObject_MockObject $dataStore
     * @param $items
     * @return string
     */
    protected function getContentRange(RqlQuery $rqlQuery, DataStoresInterface $dataStore, $items)
    {
        $limitNode = $rqlQuery->getLimit();
        $aggregateCount = [['count(id)' => 5]];
        $identifier = 'id';

        $rqlQuery->setLimit(new LimitNode(ReadInterface::LIMIT_INFINITY));
        $aggregateCountFunction = new AggregateFunctionNode('count', $identifier);
        $rqlQuery->setSelect(new SelectNode([$aggregateCountFunction]));

        $dataStore->expects($this->at(1))
            ->method('getIdentifier')
            ->willReturn($identifier);

        $dataStore->expects($this->at(2))
            ->method('query')
            ->with($rqlQuery)
            ->willReturn($aggregateCount);

        $total = current($aggregateCount)["$aggregateCountFunction"];

        if ($limitNode) {
            $offset = $limitNode->getOffset() ?? 0;
        } else {
            $offset = 0;
        }

        return "items " . ($offset + 1) . "-" . ($offset + count($items)) . "/$total";
    }
}
