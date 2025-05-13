<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\functional\DataStore\Middleware\Handler;

use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use rollun\datastore\Middleware\Handler\UpdateHandler;
use rollun\datastore\Rql\RqlQuery;
use Laminas\Diactoros\ServerRequest;

class UpdateHandlerTest extends BaseHandlerTest
{
    protected function createObject(DataStoresInterface $dataStore = null)
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

    public function attributeDataProvider()
    {
        return [
            [200, true, true],
            [201, true, false],
            [200, false, true],
            [200, false, false],
        ];
    }

    /**
     * @dataProvider attributeDataProvider
     * @param $status
     * @param $overwriteMode
     * @param $readReturn
     */
    public function testProcess($status, $overwriteMode, $readReturn)
    {
        $item = [
            'id' => 1,
            'name' => 'name',
        ];

        $request = new ServerRequest();
        $request = $request->withMethod('PUT');
        $request = $request->withAttribute('primaryKeyValue', $item['id']);
        $request = $request->withAttribute('overwriteMode', $overwriteMode);
        $request = $request->withParsedBody($item);

        $response = $this->createResponse($status, [], $item);

        $dataStore = $this->createDataStoreEmptyMock();
        $dataStore->expects($this->once())
            ->method('read')
            ->with($item['id'])
            ->willReturn($readReturn);

        $dataStore->expects($this->once())
            ->method('update')
            ->with($item)
            ->willReturn($item);

        $object = $this->createObject($dataStore);
        $this->assertDelegateCallWithAssertionCallback($this->getAssertionCallback($response), $request, $object);
    }
}
