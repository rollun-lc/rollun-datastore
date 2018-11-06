<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\unit\DataStore\DataStore;

use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use rollun\datastore\DataStore\HttpClient;
use rollun\utils\Json\Serializer;
use Zend\Http\Client;

class HttpClientTest extends TestCase
{
    protected function createObject(Client $client, $url = '', $options = null)
    {
        return new HttpClient($client, $url, $options);
    }

    public function testCreateSuccess()
    {
        $items = [
            'id' => 1,
            'name' => 'name',
        ];

        $url = '';
        $client = $this->createClientMock('POST', $url . '/1');
        $client->expects($this->once())
            ->method('setRawBody')
            ->with(Serializer::jsonSerialize($items));

        $client->expects($this->once())
            ->method('send')
            ->will();

        $object = $this->createObject($client, $url);

        $object->create($items);
    }

    protected function createResponse($status, $data)
    {

    }

    /**
     * @param $method
     * @param $uri
     * @param array $options
     * @param bool $ifMatch
     * @return Client|PHPUnit_Framework_MockObject_MockObject
     */
    protected function createClientMock($method, $uri, $options = [], $ifMatch = false)
    {
        $clientMock = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $clientMock->expects($this->once())
            ->method('setMethod')
            ->with($method);

        $headers['Content-Type'] = 'application/json';
        $headers['Accept'] = 'application/json';
        $headers['APP_ENV'] = constant('APP_ENV');

        if ($ifMatch) {
            $headers['If-Match'] = '*';
        }

        $clientMock->expects($this->once())
            ->method('setHeaders')
            ->with($headers);

        $clientMock->expects($this->once())
            ->method('setUri')
            ->with($uri);

        $clientMock->expects($this->once())
            ->method('setOptions')
            ->with($options);

        if (isset($options['login']) && isset($options['password'])) {
            unset($options['login']);
            unset($options['password']);

            $clientMock->expects($this->once())
                ->method('setAuth')
                ->with($options['login'], $options['password']);
        }

        return $clientMock;
    }
}
