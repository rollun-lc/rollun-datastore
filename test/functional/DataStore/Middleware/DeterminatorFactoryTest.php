<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\functional\DataStore\Middleware;

use Interop\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use rollun\datastore\DataStore\DataStorePluginManager;
use rollun\datastore\Middleware\Determinator;
use rollun\datastore\Middleware\Factory\DeterminatorFactory;
use TypeError;

class DeterminatorFactoryTest extends TestCase
{
    public function testInvokeSuccess()
    {
        $requestedName = DataStorePluginManager::class;

        /** @var DataStorePluginManager|PHPUnit_Framework_MockObject_MockObject $dataStorePluginManagerMock  */
        $dataStorePluginManagerMock = $this->getMockBuilder(DataStorePluginManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var ContainerInterface|PHPUnit_Framework_MockObject_MockObject $containerMock */
        $containerMock = $this->getMockBuilder(ContainerInterface::class)->getMock();
        $containerMock->expects($this->once())
            ->method('get')
            ->with($requestedName)
            ->willReturn($dataStorePluginManagerMock);

        $object = new DeterminatorFactory();
        $this->assertTrue($object->__invoke($containerMock, $requestedName) instanceof Determinator);
    }

    public function testInvokeFailIncorrectDataStorePluginManager()
    {
        $this->expectException(TypeError::class);
        $requestedName = DataStorePluginManager::class;
        $notDataStorePluginManagerMock = new class {
        };

        /** @var ContainerInterface|PHPUnit_Framework_MockObject_MockObject $containerMock */
        $containerMock = $this->getMockBuilder(ContainerInterface::class)->getMock();
        $containerMock->expects($this->once())
            ->method('get')
            ->with($requestedName)
            ->willReturn($notDataStorePluginManagerMock);

        $object = new DeterminatorFactory();
        $object->__invoke($containerMock, $requestedName);
    }
}
