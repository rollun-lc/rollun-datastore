<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\functional\DataStore\Middleware;

use PHPUnit\Framework\TestCase;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use rollun\datastore\Middleware\DataStoreRest;
use rollun\datastore\Middleware\Handler;
use SplQueue;
use Laminas\Stratigility\MiddlewarePipe;
use Laminas\Stratigility\Route;

class DataStoreRestTest extends TestCase
{
    public function testConstruct()
    {
        /** @var DataStoresInterface| $dataStoreMock */
        $dataStoreMock = $this->getMockBuilder(DataStoresInterface::class)->getMock();

        $middlewarePipe = new MiddlewarePipe();
        $middlewarePipe->pipe(new Handler\QueryHandler($dataStoreMock));
        $middlewarePipe->pipe(new Handler\ReadHandler($dataStoreMock));
        $middlewarePipe->pipe(new Handler\CreateHandler($dataStoreMock));
        $middlewarePipe->pipe(new Handler\UpdateHandler($dataStoreMock));
        $middlewarePipe->pipe(new Handler\RefreshHandler($dataStoreMock));
        $middlewarePipe->pipe(new Handler\DeleteHandler($dataStoreMock));
        $middlewarePipe->pipe(new Handler\ErrorHandler());

        //$this->assertAttributeEquals($middlewarePipe, 'middlewarePipe', new DataStoreRest($dataStoreMock));
        $object = new DataStoreRest($dataStoreMock);
        $reflection = new \ReflectionProperty($object, 'middlewarePipe');
        $reflection->setAccessible(true);
        $this->assertEquals($middlewarePipe, $reflection->getValue($object));
    }
}
