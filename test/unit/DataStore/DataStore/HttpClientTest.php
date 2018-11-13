<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\unit\DataStore\DataStore;

use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Http\Message\ResponseInterface;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\HttpClient;
use rollun\datastore\Rql\RqlQuery;
use rollun\utils\Json\Serializer;
use Zend\Http\Client;
use Zend\Http\Response;

class HttpClientTest extends TestCase
{
    protected function createObject(Client $clientMock, $url = '', $options = null)
    {
        return new HttpClient($clientMock, $url, $options);
    }

    public function testCreateSuccess()
    {
        $items = ['id' => 1, 'name' => 'name',];
        $url = '';
        $clientMock = $this->createClientMock('POST', $url);
        $clientMock->expects($this->once())
            ->method('setRawBody')
            ->with(Serializer::jsonSerialize($items));

        $response = $this->createResponse($items);
        $response->expects($this->once())
            ->method('isSuccess')
            ->willReturn(true);

        $clientMock->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $object = $this->createObject($clientMock, $url);

        $this->assertEquals($object->create($items), $items);
    }

    public function testCreateSuccessWithOverwrite()
    {
        $items = ['id' => 1, 'name' => 'name',];
        $url = '';
        $clientMock = $this->createClientMock('POST', $url, [], 1);
        $clientMock->expects($this->once())
            ->method('setRawBody')
            ->with(Serializer::jsonSerialize($items));

        $response = $this->createResponse($items);
        $response->expects($this->once())
            ->method('isSuccess')
            ->willReturn(true);

        $clientMock->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $object = $this->createObject($clientMock, $url);

        $this->assertEquals($object->create($items, 1), $items);
    }

    public function testCreateFail()
    {
        $body = 'body';
        $items = ['id' => 1, 'name' => 'name',];
        $url = '';

        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage(
            "Can't create item with id = 1. Status: . " . "ReasonPhrase: . Body: " . Serializer::jsonSerialize($body)
        );

        $clientMock = $this->createClientMock('POST', $url, [], 1);
        $clientMock->expects($this->once())
            ->method('setRawBody')
            ->with(Serializer::jsonSerialize($items));

        $response = $this->createResponse($body);
        $response->expects($this->once())
            ->method('isSuccess')
            ->willReturn(false);

        $clientMock->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $object = $this->createObject($clientMock, $url);

        $object->create($items, 1);
    }

    public function testUpdateSuccess()
    {
        $items = ['name' => 'name'];
        $itemsWithId = array_merge($items, ['id' => 1]);

        $url = '';
        $clientMock = $this->createClientMock('PUT', $url . '/1', []);

        $response = $this->createResponse($itemsWithId);
        $response->expects($this->once())
            ->method('isSuccess')
            ->willReturn(1);

        $clientMock->expects($this->once())
            ->method('setRawBody')
            ->with(Serializer::jsonSerialize($items));

        $clientMock->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $object = $this->createObject($clientMock, $url);

        $this->assertEquals($object->update($itemsWithId), $itemsWithId);
    }

    public function testUpdateSuccessWithOverwrite()
    {
        $items = ['name' => 'name'];
        $itemsWithId = array_merge($items, ['id' => 1]);

        $url = '';
        $clientMock = $this->createClientMock('PUT', $url . '/1', [], 1);

        $response = $this->createResponse($itemsWithId);
        $response->expects($this->once())
            ->method('isSuccess')
            ->willReturn(1);

        $clientMock->expects($this->once())
            ->method('setRawBody')
            ->with(Serializer::jsonSerialize($items));

        $clientMock->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $object = $this->createObject($clientMock, $url);

        $this->assertEquals($object->update($itemsWithId, 1), $itemsWithId);
    }

    public function testUpdateFail()
    {
        $body = 'body';
        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage(
            "Can't update item with id = 1. Status: . " . "ReasonPhrase: . Body: " . Serializer::jsonSerialize($body)
        );
        $items = ['name' => 'name'];
        $itemsWithId = array_merge($items, ['id' => 1]);

        $url = '';
        $clientMock = $this->createClientMock('PUT', $url . '/1', [], 1);
        $clientMock->expects($this->once())
            ->method('setRawBody')
            ->with(Serializer::jsonSerialize($items));

        $response = $this->createResponse($body);
        $response->expects($this->once())
            ->method('isSuccess')
            ->willReturn(false);

        $clientMock->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $object = $this->createObject($clientMock, $url);

        $object->update($itemsWithId, 1);
    }

    public function testReadSuccess()
    {
        $items = ['id' => 1, 'name' => 'name',];
        $url = '';
        $clientMock = $this->createClientMock('GET', $url . '/1', []);

        $response = $this->createResponse($items);
        $response->expects($this->once())
            ->method('isOk')
            ->willReturn(true);

        $clientMock->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $object = $this->createObject($clientMock, $url);

        $this->assertEquals($object->read($items['id']), $items);
    }

    public function testReadFail()
    {
        $body = 'body';
        $items = ['id' => 1, 'name' => 'name',];
        $url = '';

        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage(
            "Can't read item with id = 1. Status: . " . "ReasonPhrase: . Body: " . Serializer::jsonSerialize($body)
        );

        $clientMock = $this->createClientMock('GET', $url . '/1', []);

        $response = $this->createResponse($body);
        $response->expects($this->once())
            ->method('isOk')
            ->willReturn(false);

        $clientMock->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $object = $this->createObject($clientMock, $url);
        $object->read($items['id']);
    }

    public function testQuerySuccess()
    {
        $items = [['id' => 1, 'name' => 'name']];
        $rqlQuery = 'eq(id,1)';
        $rqlQueryObject = new RqlQuery($rqlQuery);

        $url = '';
        $clientMock = $this->createClientMock('GET', "{$url}?{$rqlQuery}", []);

        $response = $this->createResponse($items);
        $response->expects($this->once())
            ->method('isOk')
            ->willReturn(1);

        $clientMock->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $object = $this->createObject($clientMock, $url);
        $this->assertEquals($object->query($rqlQueryObject), $items);
    }

    public function testQueryFail()
    {
        $body = 'body';
        $items = [['id' => 1, 'name' => 'name']];
        $rqlQuery = 'eq(id,1)';
        $rqlQueryObject = new RqlQuery($rqlQuery);
        $url = '';

        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage(
            "Can't execute query = '{$rqlQuery}'. Status: . " . "ReasonPhrase: . Body: " . Serializer::jsonSerialize(
                $body
            )
        );

        $clientMock = $this->createClientMock('GET', "{$url}?{$rqlQuery}", []);

        $response = $this->createResponse($body);
        $response->expects($this->once())
            ->method('isOk')
            ->willReturn(false);

        $clientMock->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $object = $this->createObject($clientMock, $url);
        $this->assertEquals($object->query($rqlQueryObject), $items);
    }

    public function testDeleteSuccess()
    {
        $items = ['id' => 1, 'name' => 'name'];
        $url = '';

        $clientMock = $this->createClientMock('DELETE', "{$url}/{$items['id']}", []);

        $response = $this->createResponse($items);
        $response->expects($this->once())
            ->method('isSuccess')
            ->willReturn(1);

        $clientMock->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $object = $this->createObject($clientMock, $url);
        $this->assertEquals($object->delete($items['id']), $items);
    }

    public function testDeleteFail()
    {
        $body = 'body';
        $items = ['id' => 1, 'name' => 'name'];
        $url = '';

        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage(
            "Can't delete item with id = {$items['id']}. Status: . " .
            "ReasonPhrase: . Body: " . Serializer::jsonSerialize(
                $body
            )
        );

        $clientMock = $this->createClientMock('DELETE', "{$url}/{$items['id']}", []);

        $response = $this->createResponse($body);
        $response->expects($this->once())
            ->method('isSuccess')
            ->willReturn(false);

        $clientMock->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $object = $this->createObject($clientMock, $url);
        $object->delete($items['id']);
    }

    public function testInitClient()
    {
        $options = [
            'maxredirects' => 'foo',
            'useragent' => 'boo',
            'adapter' => 'zoo',
            'timeout' => 'voo',
            'curloptions' => 'coo',
            'login' => 'login',
            'password' => 'password',
        ];

        $clientMock = $this->createClientMock('GET', '/1', $options);
        $response = $this->createResponse('');
        $response->expects($this->once())
            ->method('isOk')
            ->willReturn(true);

        $clientMock->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $object = $this->createObject($clientMock, '', $options);
        $object->read(1);
    }

    /**
     * @param $body
     * @return ResponseInterface|PHPUnit_Framework_MockObject_MockObject
     * @throws \rollun\utils\Json\Exception
     */
    protected function createResponse($body)
    {
        $response = $this->getMockBuilder(Response::class)
            ->getMock();

        $response->expects($this->any())
            ->method('getBody')
            ->willReturn(Serializer::jsonSerialize($body));

        return $response;
    }

    /**
     * @param $method
     * @param $uri
     * @param array $options
     * @param bool $ifMatch
     * @return Client|PHPUnit_Framework_MockObject_MockObject
     */
    protected function createClientMock($method = 'GET', $uri = '', $options = [], $ifMatch = false)
    {
        $clientMockMock = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $clientMockMock->expects($this->once())
            ->method('setMethod')
            ->with($method);

        $headers['Content-Type'] = 'application/json';
        $headers['Accept'] = 'application/json';
        $headers['APP_ENV'] = constant('APP_ENV');

        if ($ifMatch) {
            $headers['If-Match'] = '*';
        }

        $clientMockMock->expects($this->once())
            ->method('setHeaders')
            ->with($headers);

        $clientMockMock->expects($this->once())
            ->method('setUri')
            ->with($uri);

        if (isset($options['login']) && isset($options['password'])) {
            $clientMockMock->expects($this->once())
                ->method('setAuth')
                ->with($options['login'], $options['password']);

            unset($options['login']);
            unset($options['password']);
        }

        $clientMockMock->expects($this->once())
            ->method('setOptions')
            ->with($options);

        return $clientMockMock;
    }
}
