<?php
/**
 * Created by PhpStorm.
 * User: lasgrate
 * Date: 04.11.18
 * Time: 14:37
 */

namespace rollun\test\functional\DataStore\Middleware\Handler;

use Interop\Http\ServerMiddleware\DelegateInterface;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Http\Message\ResponseInterface;
use rollun\datastore\Middleware\Handler\ErrorHandler;
use rollun\datastore\Middleware\RestException;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Uri;

class ErrorHandlerTest extends BaseHandlerTest
{
    public function testProcessSuccess()
    {
        $request = new ServerRequest();
        $response = $this->createResponse();
        $request = $request->withAttribute(ResponseInterface::class, $response);

        $resultResponse = null;

        /** @var DelegateInterface|PHPUnit_Framework_MockObject_MockObject $delegate */
        $delegate = $this->getMockBuilder(DelegateInterface::class)->getMock();
        $delegate->expects($this->once())
            ->method('process')
            ->with($request)
            ->willReturn($resultResponse);

        $object = new ErrorHandler();
        $this->assertEquals($object->process($request, $delegate), $resultResponse);
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

        /** @var DelegateInterface $delegate */
        $delegate = $this->getMockBuilder(DelegateInterface::class)->getMock();

        $object = new ErrorHandler();
        $object->process($request, $delegate);
    }
}
