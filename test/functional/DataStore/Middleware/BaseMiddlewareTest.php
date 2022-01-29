<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\functional\DataStore\Middleware;

use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Stream;

abstract class BaseMiddlewareTest extends TestCase
{
    /**
     * @param int $status
     * @param array $headers
     * @param array $data
     * @return Response
     */
    protected function createResponse($status = 200, $headers = [], $data = null)
    {
        $response = new Response();
        $response = $response->withStatus($status);

        foreach ($headers as $header => $value) {
            $response = $response->withHeader($header, $value);
        }

        if ($data !== null) {
            $stream = fopen("data://text/plain;base64," . base64_encode(serialize($data)), 'r');
            $response = $response->withBody(new Stream($stream));
        }

        return $response;
    }

    /**
     * @param ResponseInterface $expectedResponse
     * @param ServerRequestInterface $request
     * @param MiddlewareInterface $object
     */
    protected function assertDelegateCall(
        ResponseInterface $expectedResponse,
        ServerRequestInterface $request,
        MiddlewareInterface $object
    ) {
        /** @var PHPUnit_Framework_MockObject_MockObject|RequestHandlerInterface $mockHandler */
        $mockHandler = $this->getMockBuilder(RequestHandlerInterface::class)
            ->getMock();
        $mockHandler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse);

        $object->process($request, $mockHandler);
    }

    /**
     * @param string $identifier
     * @return \PHPUnit_Framework_MockObject_MockObject|DataStoresInterface
     */
    protected function createDataStoreEmptyMock($identifier = 'id')
    {
        $mockObject = $this->getMockBuilder(DataStoresInterface::class)
            ->getMock();

        $mockObject->expects($this->any())
            ->method('getIdentifier')
            ->willReturn($identifier);

        return $mockObject;
    }

    /**
     * @param callable $assertion
     * @param ServerRequestInterface $request
     * @param MiddlewareInterface $object
     */
    protected function assertDelegateCallWithAssertionCallback(
        callable $assertion,
        ServerRequestInterface $request,
        MiddlewareInterface $object
    ) {
        /** @var PHPUnit_Framework_MockObject_MockObject|RequestHandlerInterface $mockHandler */
        $mockHandler = $this->getMockBuilder(RequestHandlerInterface::class)
            ->getMock();
        $mockHandler->expects($this->once())
            ->method('handle')
            ->with($this->callback($assertion));

        $object->process($request, $mockHandler);
    }
}
