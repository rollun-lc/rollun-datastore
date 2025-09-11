<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\functional\DataStore\Middleware\Handler;

use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use rollun\datastore\Middleware\Handler\CreateHandler;
use rollun\datastore\Rql\RqlQuery;
use Laminas\Diactoros\ServerRequest;

class CreateHandlerTest extends BaseHandlerTest
{
    protected function createObject(DataStoresInterface $dataStore = null)
    {
        return new CreateHandler(is_null($dataStore) ? $this->createDataStoreEmptyMock() : $dataStore);
    }

    public function methodProvider()
    {
        return [
            // ['POST'],
            ['GET'],
            ['DELETE'],
            ['PUT'],
            ['PATCH'],
        ];
    }

    public function testCanUpdateSuccess()
    {
        $request = new ServerRequest();
        $request = $request->withMethod('POST');
        $request = $request->withAttribute('rqlQueryObject', new RqlQuery(''));
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
        $request = $request->withMethod('POST');
        $request = $request->withAttribute('rqlQueryObject', new RqlQuery(''));
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
        $request = $request->withMethod('POST');
        $request = $request->withAttribute('rqlQueryObject', new RqlQuery('eq(a,1)'));
        $request = $request->withParsedBody([
            1 => 1,
            2 => 'name',
        ]);

        $object = $this->createObject();
        $this->assertFalse($object->canHandle($request));
    }

    public function testProcessWithoutExistingPrimaryKeyAndRowExist()
    {
        $item = [
            'id' => 1,
            'name' => 'name',
        ];

        $response = $this->createResponse(200, [], $item);

        $request = new ServerRequest();
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody($item);
        $request = $request->withAttribute('primaryKeyValue', $item['id']);
        $request = $request->withAttribute('overwriteMode', true);

        $dataStore = $this->createDataStoreEmptyMock();
        $dataStore->expects($this->once())
            ->method('read')
            ->with($item['id'])
            ->willReturn($item);

        $dataStore->expects($this->once())
            ->method('create')
            ->with($item, true)
            ->willReturn($item);

        $object = $this->createObject($dataStore);
        $this->assertDelegateCallWithAssertionCallback($this->getAssertionCallback($response), $request, $object, false);
    }

    public function testProcessWithoutOverwriteMode()
    {
        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage("Item with id '1' already exist");

        $item = [
            'id' => 1,
            'name' => 'name',
        ];

        $request = new ServerRequest();
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody($item);
        $request = $request->withAttribute('primaryKeyValue', $item['id']);
        $request = $request->withAttribute('overwriteMode', false);

        $dataStore = $this->createDataStoreEmptyMock();
        $dataStore->expects($this->once())
            ->method('read')
            ->with($item['id'])
            ->willReturn($item);

        $object = $this->createObject($dataStore);

        /** @var RequestHandlerInterface $delegateMock */
        $delegateMock = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();
        $object->process($request, $delegateMock);
    }

    public function testProcessWithoutExistingPrimaryKeyAndRowDoesNotExist()
    {
        $item = [
            'id' => 1,
            'name' => 'name',
        ];

        $response = $this->createResponse(201, ['Location' => '/'], $item);

        $request = new ServerRequest();
        $request = $request->withUri($this->getUriMock('/'));
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody($item);
        $request = $request->withAttribute('primaryKeyValue', $item['id']);
        $request = $request->withAttribute('overwriteMode', true);

        $dataStore = $this->createDataStoreEmptyMock();
        $dataStore->expects($this->once())
            ->method('read')
            ->with($item['id'])
            ->willReturn(null);

        $dataStore->expects($this->once())
            ->method('create')
            ->with($item)
            ->willReturn($item);

        $object = $this->createObject($dataStore);
        $this->assertDelegateCallWithAssertionCallback($this->getAssertionCallback($response), $request, $object, false);
    }

    /**
     * @param $location
     * @return \PHPUnit_Framework_MockObject_MockObject|UriInterface
     */
    protected function getUriMock($location)
    {
        $mockObject = $this->getMockBuilder(UriInterface::class)
            ->getMock();
        $mockObject->expects($this->any())
            ->method('getPath')
            ->willReturn($location);

        return $mockObject;
    }
}
