<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\functional\DataStore\Middleware;

use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Http\Server\RequestHandlerInterface;
use rollun\datastore\DataStore\DataStorePluginManager;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use rollun\datastore\Middleware\Determinator;
use rollun\datastore\Middleware\RestException;
use TypeError;
use Zend\Diactoros\ServerRequest;

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
