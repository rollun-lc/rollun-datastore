<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\functional\DataStore\Middleware\Handler;

use rollun\datastore\DataStore\Interfaces\DataStoreInterface;
use rollun\datastore\Middleware\Handler\UpdateHandler;
use rollun\datastore\Rql\RqlQuery;
use Zend\Diactoros\ServerRequest;

class UpdateHandlerTest extends BaseHandlerTest
{
    protected function createObject(DataStoreInterface $dataStore = null)
    {
        return new UpdateHandler(is_null($dataStore) ? $this->createDataStoreEmptyMock() : $dataStore);
    }

    public function methodProvider()
    {
        return [
            ['POST'],
            ['GET'],
            ['DELETE'],
            // ['PUT'],
            ['PATCH'],
        ];
    }

    public function testCanUpdateSuccess()
    {
        $request = new ServerRequest();
        $request = $request->withMethod('PUT');
        $request = $request->withAttribute('rqlQueryObject', new RqlQuery(''));
        $request = $request->withAttribute('primaryKeyValue', 1);
        $request = $request->withParsedBody([
            'id' => 1,
            'name' => 'name',
        ]);

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
        $request = $request->withAttribute('primaryKeyValue', 1);
        $request = $request->withParsedBody([
            'id' => 1,
            'name' => 'name',
        ]);

        $object = $this->createObject();
        $this->assertFalse($object->canHandle($request));
    }

    public function testCanUpdateFailCauseFieldType()
    {
        $request = new ServerRequest();
        $request = $request->withMethod('PUT');
        $request = $request->withAttribute('rqlQueryObject', new RqlQuery(''));
        $request = $request->withAttribute('primaryKeyValue', 1);
        $request = $request->withParsedBody([
            1 => '1',
            2 => 'name',
        ]);

        $object = $this->createObject();
        $this->assertFalse($object->canHandle($request));
    }

    public function testCanUpdateFailWithNotEmptyRqlQueryObject()
    {
        $request = new ServerRequest();
        $request = $request->withMethod('PUT');
        $request = $request->withAttribute('rqlQueryObject', new RqlQuery('eq(a,1)'));
        $request = $request->withAttribute('primaryKeyValue', 1);
        $request = $request->withParsedBody([
            'id' => 1,
            'name' => 'name',
        ]);

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
        $request = $request->withMethod('PUT');
        $request = $request->withAttribute('primaryKeyValue', $item['id']);
        $request = $request->withParsedBody($item);

        $response = $this->createResponse(200, [], $item);

        $dataStore = $this->createDataStoreEmptyMock();

        $dataStore->expects($this->once())
            ->method('update')
            ->with($item)
            ->willReturn($item);

        $object = $this->createObject($dataStore);
        $this->assertDelegateCallWithAssertionCallback($this->getAssertionCallback($response), $request, $object);
    }
}
