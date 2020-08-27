<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\functional\DataStore\Middleware;

use Exception;
use TypeError;
use Interop\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Http\Message\ResponseInterface;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\DataStorePluginManager;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use rollun\datastore\DataStore\Interfaces\RefreshableInterface;
use rollun\datastore\DataStore\Memory;
use rollun\datastore\Middleware\DataStoreApi;
use rollun\datastore\Middleware;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Stream;
use Zend\Diactoros\Uri;
use Zend\Stratigility\MiddlewarePipe;

class DataStoreApiTest extends BaseMiddlewareTest
{
    /**
     * @var DataStoresInterface
     */
    protected $dataStore;

    /**
     * @var string
     */
    protected $resourceName;

    /**
     * @var MiddlewareInterface|DataStoreApi
     */
    protected $object;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @return ContainerInterface
     */
    protected function getContainer(): ContainerInterface
    {
        if ($this->container !== null) {
            return $this->container;
        } else {
            $this->container = require 'config/container.php';

            return $this->container;
        }
    }

    public function processObjectDataProvider()
    {
        $resourceName = 'resourceName';
        $dataStore = new Memory();

        /** @var DataStorePluginManager|PHPUnit_Framework_MockObject_MockObject $dataStorePluginManagerMock */
        $dataStorePluginManagerMock = $this->getMockBuilder(DataStorePluginManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dataStorePluginManagerMock->expects($this->once())
            ->method('has')
            ->with($resourceName)
            ->willReturn(true);

        $dataStorePluginManagerMock->expects($this->once())
            ->method('get')
            ->with($resourceName)
            ->willReturn($dataStore);

        $determinator = new Middleware\Determinator($dataStorePluginManagerMock);

        $args[] = [
            new DataStoreApi($determinator),
            $resourceName,
            $dataStore
        ];

        $container = $this->getContainer();
        $resourceName = 'memoryDataStore';

        $args[] = [
            $container->get(DataStoreApi::class),
            $resourceName,
            $container->get($resourceName),
        ];

        return $args;
    }

    /**
     * @dataProvider processObjectDataProvider
     * @param $object
     * @param $resourceName
     * @param $dataStore
     */
    public function testProcess($object, $resourceName, $dataStore)
    {
        $this->object = $object;
        $this->resourceName = $resourceName;
        $this->dataStore = $dataStore;

        $this->processSuccessCreateAndItemDoesNotExist();
        $this->processSuccessCreateAndItemExist();
        $this->processSuccessCreateAndItemExistWithOverwriteMode();
        $this->processSuccessCreateFromPath();
        $this->processSuccessRead();
        $this->processSuccessReadFromPath();

        foreach ($this->processUpdateDataProvider() as $args) {
            $this->processSuccessUpdate($object, ...$args);
            $this->processSuccessUpdateFromPath($object, ...$args);
        }

        $this->processSuccessQuery();
        $this->processSuccessQueryWithLimitAndOffset();
        $this->processSuccessDelete();
        $this->processSuccessRefresh();
        $this->processFailRefresh();

        foreach ($this->methodDataProvider() as $args) {
            $this->processWithoutHandlerHandle($object, ...$args);
        }
    }

    /**
     * @return DataStoreApi|MiddlewareInterface
     */
    public function createObject()
    {
        /** @var DataStorePluginManager|PHPUnit_Framework_MockObject_MockObject $dataStorePluginManagerMock */
        $dataStorePluginManagerMock = $this->getMockBuilder(DataStorePluginManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dataStorePluginManagerMock->expects($this->once())
            ->method('has')
            ->with($this->resourceName)
            ->willReturn(true);

        $dataStorePluginManagerMock->expects($this->once())
            ->method('get')
            ->with($this->resourceName)
            ->willReturn($this->dataStore);

        $determinator = new Middleware\Determinator($dataStorePluginManagerMock);

        return new DataStoreApi($determinator);
    }

    public function testProcessFailWithNoDataStore()
    {
        $this->expectException(TypeError::class);
        $request = new ServerRequest();
        $request = $request->withMethod('PATCH');
        $request = $request->withHeader('Content-Type', 'application/json');

        $response = new JsonResponse(null, 200);

        /** @var DataStorePluginManager|PHPUnit_Framework_MockObject_MockObject $dataStorePluginManagerMock */
        $dataStorePluginManagerMock = $this->getMockBuilder(DataStorePluginManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dataStorePluginManagerMock->expects($this->once())
            ->method('has')
            ->willReturn(true);

        $dataStorePluginManagerMock->expects($this->once())
            ->method('get')
            ->with(null);

        $determinator = new Middleware\Determinator($dataStorePluginManagerMock);

        /** @var MiddlewareInterface $object */
        $object = new DataStoreApi($determinator);

        /** @var RequestHandlerInterface $delegateMock */
        $delegateMock = $this->createMock(RequestHandlerInterface::class);

        $this->assertJsonResponseEquals($response, $object->process($request, $delegateMock));
    }

    public function testConstruct()
    {
        /** @var DataStoresInterface| $dataStoreMock */
        $dataStoreMock = $this->getMockBuilder(DataStoresInterface::class)
            ->getMock();

        $dataStorePluginManagerMock = $this->getMockBuilder(DataStorePluginManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var Middleware\Determinator|PHPUnit_Framework_MockObject_MockObject $dataStoreDeterminator */
        $dataStoreDeterminator = $this->getMockBuilder(Middleware\Determinator::class)
            ->setConstructorArgs([$dataStorePluginManagerMock])
            ->getMock();

        $middlewarePipe = new MiddlewarePipe();
        $middlewarePipe->pipe(new Middleware\ResourceResolver());
        $middlewarePipe->pipe(new Middleware\RequestDecoder());
        $middlewarePipe->pipe(new Middleware\DataStoreRest($dataStoreMock));
        $middlewarePipe->pipe(new Middleware\JsonRenderer());

        $objects = new DataStoreApi($dataStoreDeterminator);
        $this->assertAttributeEquals($middlewarePipe, 'middlewarePipe', $objects);
    }

    protected function processSuccessCreateAndItemDoesNotExist()
    {
        $data = [
            'id' => 1,
            'name' => 'name',
        ];
        $request = new ServerRequest();
        $request = $request->withMethod('POST');
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withAttribute('resourceName', $this->resourceName);
        $request = $request->withAttribute('withContentRange', true);

        $resource = "data://text/plain;base64," . base64_encode(json_encode($data));
        $request = $request->withBody(new Stream(fopen($resource, 'r')));
        $response = new JsonResponse($data, 201);
        $response = $response->withHeader(
            'Location',
            $request->getUri()
                ->getPath()
        );
        $response = $response->withHeader('Datastore-Scheme', '');
        $response = $response->withHeader('X_DATASTORE_IDENTIFIER', 'id');

        /** @var RequestHandlerInterface $delegateMock */
        $delegateMock = $this->createMock(RequestHandlerInterface::class);

        $this->assertJsonResponseEquals($response, $this->object->process($request, $delegateMock));
    }

    protected function processSuccessCreateAndItemExist()
    {
        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage("Item with id '1' already exist");
        $data = [
            'id' => 1,
            'name' => 'name',
        ];
        $this->dataStore->create($data);
        $request = new ServerRequest();
        $request = $request->withMethod('POST');
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withAttribute('resourceName', $this->resourceName);

        $resource = "data://text/plain;base64," . base64_encode(json_encode($data));
        $request = $request->withBody(new Stream(fopen($resource, 'r')));

        /** @var RequestHandlerInterface $delegateMock */
        $delegateMock = $this->createMock(RequestHandlerInterface::class);
        $this->object->process($request, $delegateMock);
    }

    protected function processSuccessCreateAndItemExistWithOverwriteMode()
    {
        $data = [
            'id' => 1,
            'name' => 'name',
        ];
        $this->dataStore->create($data);
        $request = new ServerRequest();
        $request = $request->withMethod('POST');
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withHeader('If-Match', '*');
        $request = $request->withAttribute('resourceName', $this->resourceName);

        $resource = "data://text/plain;base64," . base64_encode(json_encode($data));
        $request = $request->withBody(new Stream(fopen($resource, 'r')));
        $response = new JsonResponse($data, 201);
        $response = $response->withHeader(
            'Location',
            $request->getUri()
                ->getPath()
        );

        /** @var RequestHandlerInterface $delegateMock */
        $delegateMock = $this->createMock(RequestHandlerInterface::class);

        $this->assertJsonResponseEquals(
            $response,
            $this->object->process($request, $delegateMock)
        );
    }

    protected function processSuccessCreateFromPath()
    {
        $data = [
            'id' => 1,
            'name' => 'name',
        ];
        $request = new ServerRequest();
        $request = $request->withMethod('POST');
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withUri(new Uri("/api/datastore/{$this->resourceName}"));

        $resource = "data://text/plain;base64," . base64_encode(json_encode($data));
        $request = $request->withBody(new Stream(fopen($resource, 'r')));
        $response = new JsonResponse($data, 201);
        $response = $response->withHeader(
            'Location',
            $request->getUri()
                ->getPath()
        );

        /** @var RequestHandlerInterface $delegateMock */
        $delegateMock = $this->createMock(RequestHandlerInterface::class);

        $this->assertJsonResponseEquals(
            $response,
            $this->object->process($request, $delegateMock)
        );
    }

    protected function processSuccessRead()
    {
        $data = [
            'id' => 1,
            'name' => 'name',
        ];

        $this->dataStore->create($data);

        $request = new ServerRequest();
        $request = $request->withMethod('GET');
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withAttribute('resourceName', $this->resourceName);
        $request = $request->withAttribute('id', $data['id']);

        $response = new JsonResponse($data, 200);

        /** @var RequestHandlerInterface $delegateMock */
        $delegateMock = $this->createMock(RequestHandlerInterface::class);

        $this->assertJsonResponseEquals(
            $response,
            $this->object->process($request, $delegateMock)
        );
    }

    protected function processSuccessReadFromPath()
    {
        $data = [
            'id' => 1,
            'name' => 'name',
        ];

        $this->dataStore->create($data);

        $request = new ServerRequest();
        $request = $request->withMethod('GET');
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withUri(new Uri("/api/datastore/{$this->resourceName}/{$data['id']}"));

        $response = new JsonResponse($data, 200);

        /** @var RequestHandlerInterface $delegateMock */
        $delegateMock = $this->createMock(RequestHandlerInterface::class);

        $this->assertJsonResponseEquals(
            $response,
            $this->object->process($request, $delegateMock)
        );
    }

    protected function processSuccessUpdate(MiddlewareInterface $object, $status, $overwriteMode, $itemExist)
    {
        $data = [
            'id' => 1,
            'name' => 'name',
        ];

        if ($itemExist) {
            $this->dataStore->create($data);
        } elseif (!$overwriteMode) {
            $this->expectException(DataStoreException::class);
            $this->expectExceptionMessage("Item doesn't exist with id = 1");
        }

        $request = new ServerRequest();
        $request = $request->withMethod('PUT');
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withAttribute('resourceName', $this->resourceName);
        $request = $request->withAttribute('id', $data['id']);

        $resource = "data://text/plain;base64," . base64_encode(json_encode($data));
        $request = $request->withBody(new Stream(fopen($resource, 'r')));

        if ($overwriteMode) {
            $request = $request->withHeader('If-Match', '*');
        }

        $response = new JsonResponse($data, $status);

        /** @var RequestHandlerInterface $delegateMock */
        $delegateMock = $this->createMock(RequestHandlerInterface::class);

        $this->assertJsonResponseEquals(
            $response,
            $this->object->process($request, $delegateMock)
        );
    }

    protected function processSuccessUpdateFromPath(
        MiddlewareInterface $object,
        $status,
        $overwriteMode,
        $itemExist
    ) {
        $data = [
            'id' => 1,
            'name' => 'name',
        ];

        if ($itemExist) {
            $this->dataStore->create($data);
        } elseif (!$overwriteMode) {
            $this->expectException(DataStoreException::class);
            $this->expectExceptionMessage("Item doesn't exist with id = 1");
        }

        $request = new ServerRequest();
        $request = $request->withMethod('PUT');
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withUri(new Uri("/api/datastore/{$this->resourceName}/{$data['id']}"));

        $resource = "data://text/plain;base64," . base64_encode(json_encode($data));
        $request = $request->withBody(new Stream(fopen($resource, 'r')));

        if ($overwriteMode) {
            $request = $request->withHeader('If-Match', '*');
        }

        $response = new JsonResponse($data, $status);

        /** @var RequestHandlerInterface $delegateMock */
        $delegateMock = $this->createMock(RequestHandlerInterface::class);

        $this->assertJsonResponseEquals(
            $response,
            $this->object->process($request, $delegateMock)
        );
    }

    protected function processSuccessQuery()
    {
        $data1 = [
            'id' => 1,
            'name' => 'name',
        ];
        $data2 = [
            'id' => 2,
            'name' => 'name',
        ];

        $this->dataStore->create($data1);
        $this->dataStore->create($data2);

        $request = new ServerRequest();
        $request = $request->withMethod('GET');
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withAttribute('resourceName', $this->resourceName);

        $uri = new Uri("/api/datastore/{$this->resourceName}?eq(id,1)");
        $request = $request->withUri($uri);

        $response = new JsonResponse(
            [$data1],
            200,
            [
                'Content-Range' => 'items 0-1/1',
            ]
        );

        /** @var RequestHandlerInterface $delegateMock */
        $delegateMock = $this->createMock(RequestHandlerInterface::class);

        $this->assertJsonResponseEquals(
            $response,
            $this->object->process($request, $delegateMock)
        );
    }

    protected function processSuccessQueryWithLimitAndOffset()
    {
        $data1 = [
            'id' => 1,
            'name' => 'name',
        ];
        $data2 = [
            'id' => 2,
            'name' => 'name',
        ];

        $this->dataStore->create($data1);
        $this->dataStore->create($data2);

        $request = new ServerRequest();
        $request = $request->withMethod('GET');
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withAttribute('resourceName', $this->resourceName);

        $uri = new Uri("/api/datastore/{$this->resourceName}?eq(id,1)&limit(1,0)");
        $request = $request->withUri($uri);

        $response = new JsonResponse(
            [$data1],
            200,
            [
                'Content-Range' => 'items 0-1/1',
            ]
        );

        /** @var RequestHandlerInterface $delegateMock */
        $delegateMock = $this->createMock(RequestHandlerInterface::class);

        $this->assertJsonResponseEquals(
            $response,
            $this->object->process($request, $delegateMock)
        );
    }

    protected function processSuccessDelete()
    {
        $data = [
            'id' => 1,
            'name' => 'name',
        ];

        $this->dataStore->create($data);

        $request = new ServerRequest();
        $request = $request->withMethod('DELETE');
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withAttribute('resourceName', $this->resourceName);
        $request = $request->withAttribute('id', $data['id']);

        $response = new JsonResponse($data, 200);

        /** @var RequestHandlerInterface $delegateMock */
        $delegateMock = $this->createMock(RequestHandlerInterface::class);

        $this->assertJsonResponseEquals(
            $response,
            $this->object->process($request, $delegateMock)
        );
    }

    protected function processSuccessRefresh()
    {
        $this->dataStore = new class extends Memory implements RefreshableInterface
        {
            public function refresh()
            {
            }
        };

        $request = new ServerRequest();
        $request = $request->withMethod('PATCH');
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withAttribute('resourceName', $this->resourceName);

        $response = new JsonResponse('', 200);

        /** @var RequestHandlerInterface $delegateMock */
        $delegateMock = $this->createMock(RequestHandlerInterface::class);

        $this->assertJsonResponseEquals(
            $response,
            $this->object->process($request, $delegateMock)
        );
    }

    protected function processFailRefresh()
    {
        $this->expectException(Middleware\RestException::class);
        $this->expectExceptionMessage("DataStore is not implement RefreshableInterface");
        $request = new ServerRequest();
        $request = $request->withMethod('PATCH');
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withAttribute('resourceName', $this->resourceName);

        $response = new JsonResponse(null, 200);

        /** @var RequestHandlerInterface $delegateMock */
        $delegateMock = $this->createMock(RequestHandlerInterface::class);

        $this->assertJsonResponseEquals(
            $response,
            $this->object->process($request, $delegateMock)
        );
    }

    protected function processWithoutHandlerHandle($method, Exception $exception)
    {
        $this->expectException(get_class($exception));
        $this->expectExceptionMessage($exception->getMessage());

        $request = new ServerRequest();
        $request = $request->withMethod($method);
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withAttribute('resourceName', $this->resourceName);

        $response = new JsonResponse('', 200);

        /** @var RequestHandlerInterface $delegateMock */
        $delegateMock = $this->createMock(RequestHandlerInterface::class);

        $this->assertJsonResponseEquals(
            $response,
            $this->object->process($request, $delegateMock)
        );
    }

    public function processUpdateDataProvider()
    {
        return [
            [200, true, true],
            [201, true, false],
            [200, false, true],
            [200, false, false],
        ];
    }

    public function methodDataProvider()
    {
        return [
            [
                'POST',
                new Middleware\RestException(
                    "No one datastore handler was executed. " . "Method: 'POST'. " . "Uri: ''. "
                    . "ParsedBody: 'null'. " . "Attributes: '{\"resourceName\":\"resourceName\","
                    . "\"primaryKeyValue\":null,\"overwriteMode\":false,\"rqlQueryObject\":{}}'."
                ),
            ],
            [
                'DELETE',
                new Middleware\RestException(
                    "No one datastore handler was executed. " . "Method: 'DELETE'. " . "Uri: ''. "
                    . "ParsedBody: 'null'. " . "Attributes: '{\"resourceName\":\"resourceName\","
                    . "\"primaryKeyValue\":null,\"overwriteMode\":false,\"rqlQueryObject\":{}}'."
                ),
            ],
            [
                'PUT',
                new Middleware\RestException(
                    "No one datastore handler was executed. " . "Method: 'PUT'. " . "Uri: ''. "
                    . "ParsedBody: 'null'. " . "Attributes: '{\"resourceName\":\"resourceName\","
                    . "\"primaryKeyValue\":null,\"overwriteMode\":false,\"rqlQueryObject\":{}}'."
                ),
            ],
            [
                'PATCH',
                new Middleware\RestException("DataStore is not implement RefreshableInterface"),
            ],
        ];
    }

    public function assertJsonResponseEquals(ResponseInterface $expected, ResponseInterface $actual)
    {
        $this->assertEquals(
            $expected->getBody()
                ->getContents(),
            $actual->getBody()
                ->getContents()
        );

        $this->assertEquals($expected->getHeaders(), $actual->getHeaders());
        $this->assertEquals($expected->getStatusCode(), $actual->getStatusCode());
    }
}
