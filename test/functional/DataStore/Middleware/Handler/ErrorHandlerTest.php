<?php
/**
 * Created by PhpStorm.
 * User: lasgrate
 * Date: 04.11.18
 * Time: 14:37
 */

namespace rollun\test\functional\DataStore\Middleware\Handler;

use PHPUnit_Framework_MockObject_MockObject;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use rollun\datastore\Middleware\Handler\ErrorHandler;
use rollun\datastore\Middleware\RestException;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Uri;

class ErrorHandlerTest extends BaseHandlerTest
{
    public function testProcessSuccess()
    {
        $request = new ServerRequest();
        $response = $this->createResponse();
        $request = $request->withAttribute(ResponseInterface::class, $response);

        $resultResponse = new Response();

        /** @var RequestHandlerInterface|PHPUnit_Framework_MockObject_MockObject $handler */
        $handler = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();
        $handler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($resultResponse);

        $object = new ErrorHandler();
        $this->assertEquals($object->process($request, $handler), $resultResponse);
    }

    public function testProcessFail()
    {
        $this->expectException(RestException::class);
        $this->expectExceptionMessage(
            "No one datastore handler was executed. "
            . "Method: 'method'. "
            . "Uri: '/some/path'. "
            . "ParsedBody: 'null'. "
            . "Attributes: '{\"a\":\"b\",\"c\":\"d\"}'."
        );
        $request = new ServerRequest();
        $request = $request->withMethod('method');
        $request = $request->withAttribute('a', 'b');
        $request = $request->withAttribute('c', 'd');

        $uri = new Uri();
        $uri = $uri->withPath('/some/path');
        $request = $request->withUri($uri);

        /** @var RequestHandlerInterface $delegate */
        $delegate = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();

        $object = new ErrorHandler();
        $object->process($request, $delegate);
    }
}
