<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\functional\DataStore\Middleware;

use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Http\Server\RequestHandlerInterface;
use rollun\datastore\DataStore\Aspect\AspectTyped;
use rollun\datastore\DataStore\BaseDto;
use rollun\datastore\DataStore\DataStorePluginManager;
use rollun\datastore\DataStore\Formatter\IntFormatter;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use rollun\datastore\DataStore\Memory;
use rollun\datastore\DataStore\Type\TypeInt;
use rollun\datastore\DataStore\Type\TypeString;
use rollun\datastore\Middleware\Determinator;
use rollun\datastore\Middleware\ResourceResolver;
use rollun\datastore\Middleware\RestException;
use rollun\test\unit\DataStore\DataStore\Aspect\StringFormatter;
use TypeError;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;

class DeterminatorTest extends TestCase
{
    public function testProcessSuccess()
    {
        $this->expectException(RestException::class);
        $this->expectExceptionMessage(
            "No one datastore handler was executed. "
            . "Method: 'GET'. "
            . "Uri: ''. "
            . "ParsedBody: 'null'. "
            . "Attributes: '{\"resourceName\":\"dataStoreService\"}'. "
        );
        $serviceName = 'dataStoreService';

        /** @var DataStoresInterface $dataStoreMock */
        $dataStoreMock = $this->getMockBuilder(DataStoresInterface::class)->getMock();

        /** @var DataStorePluginManager|PHPUnit_Framework_MockObject_MockObject $dataStorePluginManagerMock  */
        $dataStorePluginManagerMock = $this->getMockBuilder(DataStorePluginManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $request = new ServerRequest();
        $request = $request->withAttribute('resourceName', $serviceName);

        /** @var RequestHandlerInterface|PHPUnit_Framework_MockObject_MockObject $delegate */
        $delegate = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();

        $dataStorePluginManagerMock->expects($this->once())
            ->method('has')
            ->with($serviceName)
            ->willReturn(true);

        $dataStorePluginManagerMock->expects($this->once())
            ->method('get')
            ->with($serviceName)
            ->willReturn($dataStoreMock);

        $object = new Determinator($dataStorePluginManagerMock);
        $object->process($request, $delegate);
    }

    public function testProcessWithDatastoreSchemeHeader()
    {
        $serviceName = 'dataStoreService';
        $dataStore = new AspectTyped(new Memory(), [
            'fieldInt' => [
                'type' => TypeInt::class,
                'formatter' => IntFormatter::class,
            ],
            'fieldString' => [
                'type' => TypeString::class,
                'formatter' => StringFormatter::class,
            ]
        ], UserDto::class);

        /** @var DataStorePluginManager|PHPUnit_Framework_MockObject_MockObject $dataStorePluginManagerMock */
        $dataStorePluginManagerMock = $this->getMockBuilder(DataStorePluginManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dataStorePluginManagerMock->expects($this->once())
            ->method('has')
            ->with($serviceName)
            ->willReturn(true);

        $dataStorePluginManagerMock->expects($this->once())
            ->method('get')
            ->with($serviceName)
            ->willReturn($dataStore);

        /** @var RequestHandlerInterface|PHPUnit_Framework_MockObject_MockObject $delegate */
        $delegate = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();

        $delegate->expects($this->once())
            ->method('handle')
            ->willReturn(new Response());

        $request = new ServerRequest();
        $request = $request->withAttribute(ResourceResolver::RESOURCE_NAME, $serviceName);
        $request = $request->withAttribute(ResourceResolver::PRIMARY_KEY_VALUE, 1);

        $object = new Determinator($dataStorePluginManagerMock);
        $response = $object->process($request, $delegate);

        $this->assertEquals(json_encode($dataStore->getScheme()), current($response->getHeader('Datastore-Scheme')));
    }

    public function testProcessFail()
    {
        $this->expectException(TypeError::class);
        $serviceName = 'dataStoreService';

        $notDataStore = new class {
        };

        /** @var DataStorePluginManager|PHPUnit_Framework_MockObject_MockObject $dataStorePluginManagerMock */
        $dataStorePluginManagerMock = $this->getMockBuilder(DataStorePluginManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $request = new ServerRequest();
        $request = $request->withAttribute('resourceName', $serviceName);

        /** @var RequestHandlerInterface|PHPUnit_Framework_MockObject_MockObject $delegate */
        $delegate = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();

        $dataStorePluginManagerMock->expects($this->once())
            ->method('has')
            ->with($serviceName)
            ->willReturn(true);

        $dataStorePluginManagerMock->expects($this->once())
            ->method('get')
            ->with($serviceName)
            ->willReturn($notDataStore);

        $object = new Determinator($dataStorePluginManagerMock);
        $object->process($request, $delegate);
    }
}


class UserDto extends BaseDto
{
    protected $id;

    protected $name;

    public function getId()
    {
        return $this->id->toTypeValue();
    }

    public function getName()
    {
        return $this->name->toTypeValue();
    }
}
