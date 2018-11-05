<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\functional\DataStore\Middleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Http\Message\ServerRequestInterface;
use rollun\datastore\DataStore\DataStorePluginManager;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use rollun\datastore\DataStore\Memory;
use rollun\datastore\Middleware\DataStoreApi;
use rollun\datastore\Middleware;
use SplQueue;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Stream;
use Zend\Stratigility\Route;

class DataStoreApiTest extends BaseMiddlewareTest
{
    /**
     * @var DataStoresInterface
     */
    protected $dataStore;

    /**
     * @var DataStoreApi|MiddlewareInterface
     */
    protected $object;

    /**
     * @var string
     */
    protected $resourceName;

    public function setUp()
    {
        $this->resourceName = 'resourceName';
        $this->dataStore = new Memory(['id', 'name']);

        /** @var DataStorePluginManager|PHPUnit_Framework_MockObject_MockObject $dataStorePluginManagerMock */
        $dataStorePluginManagerMock = $this->getMockBuilder(DataStorePluginManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dataStorePluginManagerMock->expects($this->once())
            ->method('get')
            ->with($this->resourceName)
            ->willReturn($this->dataStore);

        $determinator = new Middleware\Determinator($dataStorePluginManagerMock);
        $this->object = new DataStoreApi($determinator);
    }

    public function testConstruct()
    {
        /** @var DataStoresInterface| $dataStoreMock */
        $dataStoreMock = $this->getMockBuilder(DataStoresInterface::class)
            ->getMock();

        /** @var Middleware\Determinator|PHPUnit_Framework_MockObject_MockObject $dataStoreDeterminator */
        $dataStoreDeterminator = $this->getMockBuilder(Middleware\Determinator::class)
            ->setConstructorArgs(
                [
                    $this->getMockBuilder(DataStorePluginManager::class)
                        ->disableOriginalConstructor()
                        ->getMock(),
                ]
            )
            ->getMock();

        $splObject = new SplQueue();
        $splObject->enqueue(new Route('/', new Middleware\ResourceResolver()));
        $splObject->enqueue(new Route('/', new Middleware\RequestDecoder()));
        $splObject->enqueue(new Route('/', new Middleware\DataStoreRest($dataStoreMock)));
        $splObject->enqueue(new Route('/', new Middleware\JsonRenderer()));

        $objects = new DataStoreApi($dataStoreDeterminator);
        $this->assertAttributeEquals($splObject, 'pipeline', $objects);
    }

    public function testProcessSuccessCreate()
    {
        $data = [
            'id' => 1,
            'name' => 'name',
        ];
        $request = new ServerRequest();
        $request = $request->withMethod('POST');
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withAttribute('resourceName', $this->resourceName);

        $resource = "data://text/plain;base64," . base64_encode(json_encode($data));
        $request = $request->withBody(new Stream(fopen($resource, 'r')));
        $response = new JsonResponse($data, 201);
        $response = $response->withHeader('Location', $request->getUri()->getPath());

        $this->assertDelegateCallWithAssertionCallback($this->getAssertionCallback($response), $request, $object);
        $this->assertDelegateCallWithResponseAssertion($response, $request, $this->object);
    }

    public function getAssertionCallback();
}
