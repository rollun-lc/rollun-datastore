<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\functional\DataStore\Middleware\Handler;

use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use rollun\datastore\Middleware\Handler\ReadHandler;
use rollun\datastore\Rql\RqlQuery;
use Zend\Diactoros\ServerRequest;

class ReadHandlerTest extends BaseHandlerTest
{
    protected function createObject(DataStoresInterface $dataStore = null)
    {
        return new ReadHandler(is_null($dataStore) ? $this->createDataStoreEmptyMock() : $dataStore);
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
        $request = $request->withAttribute('primaryKeyValue', 1);
        $request = $request->withAttribute('rqlQueryObject', new RqlQuery(''));

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
        $request = $request->withAttribute('primaryKeyValue', 1);
        $request = $request->withAttribute('rqlQueryObject', new RqlQuery(''));

        $object = $this->createObject();
        $this->assertFalse($object->canHandle($request));
    }

    public function testCanHandleFailCausePrimaryKey()
    {
        $request = new ServerRequest();
        $request = $request->withMethod('GET');
        $request = $request->withAttribute('primaryKeyValue', null);
        $request = $request->withAttribute('rqlQueryObject', new RqlQuery(''));

        $object = $this->createObject();
        $this->assertFalse($object->canHandle($request));
    }

    public function testCanHandleFailCauseRqlQueryNotEmpty()
    {
        $request = new ServerRequest();
        $request = $request->withMethod('GET');
        $request = $request->withAttribute('primaryKeyValue', 1);
        $request = $request->withAttribute('rqlQueryObject', new RqlQuery('eq(a,1)'));

        $object = $this->createObject();
        $this->assertFalse($object->canHandle($request));
    }

    public function testProcess()
    {
        $item = [
            'id' => 1,
            'name' => 'name'
        ];

        $request = new ServerRequest();
        $request = $request->withMethod('GET');
        $request = $request->withAttribute('primaryKeyValue', $item['id']);

        $response = $this->createResponse(200, [], $item);

        $dataStore = $this->createDataStoreEmptyMock();
        $dataStore->expects($this->once())
            ->method('read')
            ->with($item['id'])
            ->willReturn($item);

        $object = $this->createObject($dataStore);
        $this->assertDelegateCall($response, $request, $object);
    }
}
