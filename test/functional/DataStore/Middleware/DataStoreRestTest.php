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
use Zend\Stratigility\Route;

class DataStoreRestTest extends TestCase
{
    public function testConstruct()
    {
        /** @var DataStoresInterface| $dataStoreMock */
        $dataStoreMock = $this->getMockBuilder(DataStoresInterface::class)->getMock();

        $splObject = new SplQueue();
        $splObject->enqueue(new Route('/', new Handler\QueryHandler($dataStoreMock)));
        $splObject->enqueue(new Route('/', new Handler\ReadHandler($dataStoreMock)));
        $splObject->enqueue(new Route('/', new Handler\CreateHandler($dataStoreMock)));
        $splObject->enqueue(new Route('/', new Handler\UpdateHandler($dataStoreMock)));
        $splObject->enqueue(new Route('/', new Handler\RefreshHandler($dataStoreMock)));
        $splObject->enqueue(new Route('/', new Handler\DeleteHandler($dataStoreMock)));
        $splObject->enqueue(new Route('/', new Handler\ErrorHandler($dataStoreMock)));

        $this->assertAttributeEquals($splObject, 'pipeline', new DataStoreRest($dataStoreMock));
    }
}
