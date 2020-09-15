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
use rollun\logger\LifeCycleToken;
use rollun\utils\Json\Serializer;
use Zend\Http\Client;
use Zend\Http\Header\HeaderInterface;
use Zend\Http\Headers;
use Zend\Http\Response;

class HttpClientTest extends TestCase
{
    /**
     * @var LifeCycleToken
     */
    protected $container;

    protected function setUp()
    {
        global $container;
        $this->container = $container;
    }

    protected function createObject(Client $clientMock, $url = '', $options = [])
    {
        return new HttpClient($clientMock, $url, $options, $this->container->get(LifeCycleToken::class));
    }

    /**
     * @param Client $clientMock
     * @param string $url
     * @param array  $options
     *
     * @return HttpClient
     */
    protected function createObjectForMultiCreate(Client $clientMock, $url = '', $options = [])
    {
        return new class($clientMock, $url, $options, $this->container->get(LifeCycleToken::class)) extends HttpClient {
            /**
             * @inheritDoc
             */
            protected function sendHead()
            {
                return ['X_MULTI_CREATE' => true];
            }
        };
    }

    public function testMultiCreateSuccess()
    {
        $items = [['id' => 1, 'name' => 'name']];
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

        $this->assertEquals($this->createObjectForMultiCreate($clientMock, $url)->multiCreate($items), $items);
    }

    public function testMultiCreateFail()
    {
        $items = [['id' => 1, 'name' => 'name']];
        $url = '';

        $clientMock = $this->createClientMock('POST', $url);
        $clientMock->expects($this->once())
            ->method('setRawBody')
            ->with(Serializer::jsonSerialize($items));

        $response = $this->createResponse('');
        $response->expects($this->once())
            ->method('isSuccess')
            ->willReturn(false);

        $clientMock->expects($this->once())
            ->method('send')
            ->willReturn($response);

        try {
            $this->createObjectForMultiCreate($clientMock, $url)->multiCreate($items);
        } catch (DataStoreException $e) {
            $this->assertEquals('Can\'t create items POST    ""', $e->getMessage());
        }

        try {
            $this->createObjectForMultiCreate($clientMock, $url)->multiCreate(['id' => 1, 'name' => 'name']);
        } catch (DataStoreException $e) {
            $this->assertEquals("Collection of arrays expected", $e->getMessage());
        }
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
        $object->setIdendifier('id');

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
        $object->setIdendifier('id');

        $this->assertEquals($object->update($itemsWithId, 1), $itemsWithId);
    }

    public function testUpdateFail()
    {
        $body = 'body';
        $this->expectException(DataStoreException::class);
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
        $object->setIdendifier('id');

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
        $headers['X-Life-Cycle-Token'] = $this->container->get(LifeCycleToken::class)->toString();
        $headers['LifeCycleToken'] = $this->container->get(LifeCycleToken::class)->toString();

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

    public function testException()
    {
        $body = 'body';
        $items = ['id' => 1, 'name' => 'name',];
        $url = 'www.example.com';
        $method = 'POST';
        $status = 500;
        $reasonPhrase = 'Internal Server Error';

        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage("Can't create item {$method} {$url} ${status} {$reasonPhrase}");

        $clientMock = $this->createClientMock($method, $url, [], 1);
        $clientMock->expects($this->once())
            ->method('setRawBody')
            ->with(Serializer::jsonSerialize($items));

        $response = $this->createResponse($body);
        $response->expects($this->any())
            ->method('isSuccess')
            ->willReturn(false);

        $response->expects($this->any())
            ->method('getStatusCode')
            ->willReturn($status);

        $response->expects($this->any())
            ->method('getReasonPhrase')
            ->willReturn($reasonPhrase);

        $clientMock->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $object = $this->createObject($clientMock, $url);

        $object->create($items, 1);
    }

    public function testExceptionWithRedirect()
    {
        $body = 'body';
        $items = ['id' => 1, 'name' => 'name',];
        $url = 'www.example.com';
        $method = 'POST';
        $status = 301;
        $reasonPhrase = 'Moved Permanently';
        $location = 'www.example.com/index.php';

        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage(
            "Can't create item {$method} {$url} ${status} {$reasonPhrase} \"{$body}\" New location is '{$location}'"
        );

        $clientMock = $this->createClientMock($method, $url, [], 1);
        $clientMock->expects($this->once())
            ->method('setRawBody')
            ->with(Serializer::jsonSerialize($items));

        $response = $this->createResponse($body);
        $response->expects($this->any())
            ->method('isSuccess')
            ->willReturn(false);

        $response->expects($this->any())
            ->method('getStatusCode')
            ->willReturn($status);

        $response->expects($this->any())
            ->method('getReasonPhrase')
            ->willReturn($reasonPhrase);

        $header = $this->getMockBuilder(HeaderInterface::class)->disableOriginalConstructor()->getMock();
        $header->expects($this->once())
            ->method('getFieldValue')
            ->willReturn($location);

        $headers = $this->getMockBuilder(Headers::class)->disableOriginalConstructor()->getMock();
        $headers->expects($this->once())
            ->method('get')
            ->with('Location')
            ->willReturn($header);

        $response->expects($this->any())
            ->method('getHeaders')
            ->willReturn($headers);

        $clientMock->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $object = $this->createObject($clientMock, $url);

        $object->create($items, 1);
    }

    public function testHeaderIdentifier()
    {
        $items = ['test' => 1, 'name' => 'name',];
        $url = '';
        $clientMock = $this->createClientMock('GET', $url . '/1', []);

        $response = $this->createResponse($items);
        $response->expects($this->once())
            ->method('isOk')
            ->willReturn(true);

        $header = $this->createMock(HeaderInterface::class);
        $header->expects($this->once())
            ->method('getFieldValue')
            ->willReturn('test');

        $headers = $this->createMock(Headers::class);
        $headers->expects($this->once())
            ->method('get')
            ->with('X_DATASTORE_IDENTIFIER')
            ->willReturn($header);
        $headers->expects($this->once())
            ->method('has')
            ->with('X_DATASTORE_IDENTIFIER')
            ->willReturn(true);

        $response->expects($this->once())
            ->method('getHeaders')
            ->willReturn($headers);

        $clientMock->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $object = $this->createObject($clientMock, $url);

        $object->read($items['test']);

        $this->assertEquals('test', $object->getIdentifier());
    }
}
