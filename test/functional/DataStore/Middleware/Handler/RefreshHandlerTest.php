<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\functional\DataStore\Middleware\Handler;

use Psr\Http\Server\RequestHandlerInterface;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use rollun\datastore\DataStore\Interfaces\RefreshableInterface;
use rollun\datastore\DataStore\Memory;
use rollun\datastore\Middleware\Handler\RefreshHandler;
use rollun\datastore\Middleware\RestException;
use rollun\datastore\Rql\RqlQuery;
use Laminas\Diactoros\ServerRequest;

class RefreshHandlerTest extends BaseHandlerTest
{
    protected function createObject(DataStoresInterface $dataStore = null)
    {
        return new RefreshHandler(is_null($dataStore) ? $this->createDataStoreEmptyMock() : $dataStore);
    }

    public function methodProvider()
    {
        return [
            ['POST'],
            ['GET'],
            ['DELETE'],
            ['PUT'],
            // ['PATCH'],
        ];
    }

    public function testCanUpdateSuccess()
    {
        $request = new ServerRequest();
        $request = $request->withMethod('PATCH');
        $request = $request->withAttribute('rqlQueryObject', new RqlQuery(''));

        $object = $this->createObject();
        $this->assertTrue($object->canHandle($request));
    }

    /**
     * @dataProvider methodProvider
     * @param $notValidMethod
     */
    public function testCanUpdateFailCauseMethod($notValidMethod)
    {
        $request = new ServerRequest();
        $request = $request->withMethod($notValidMethod);
        $request = $request->withAttribute('rqlQueryObject', new RqlQuery(''));

        $object = $this->createObject();
        $this->assertFalse($object->canHandle($request));
    }

    public function testCanHandleFailCauseRqlQueryNotEmpty()
    {
        $request = new ServerRequest();
        $request = $request->withMethod('PATCH');
        $request = $request->withAttribute('rqlQueryObject', new RqlQuery('eq(a,1)'));

        $object = $this->createObject();
        $this->assertFalse($object->canHandle($request));
    }

    public function testProcessSuccess()
    {
        $request = new ServerRequest();
        $request = $request->withMethod('PATCH');
        $response = $this->createResponse(200);

        $dataStore = $this->getMockBuilder(TestInterface::class)
            ->getMock();
        $dataStore->expects($this->once())
            ->method('refresh');

        $object = $this->createObject($dataStore);
        $this->assertDelegateCallWithAssertionCallback($this->getAssertionCallback($response), $request, $object);
    }

    public function testProcessFail()
    {
        $this->expectException(RestException::class);
        $this->expectExceptionMessage('DataStore is not implement RefreshableInterface');
        $request = new ServerRequest();
        $request = $request->withMethod('PATCH');

        /** @var RequestHandlerInterface $delegateMock */
        $delegateMock = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();
        $dataStore = new Memory(['id']);
        $object = $this->createObject($dataStore);
        $object->process($request, $delegateMock);
    }
}

interface TestInterface extends DataStoresInterface, RefreshableInterface {}
