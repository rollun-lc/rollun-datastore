<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\functional\DataStore\Middleware\Handler;

use Exception;
use Interop\Http\ServerMiddleware\DelegateInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use rollun\datastore\Middleware\Handler\AbstractHandler;
use rollun\datastore\Middleware\JsonRenderer;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;

class BaseHandlerTest extends TestCase
{
    /**
     * @param string $identifier
     * @return \PHPUnit_Framework_MockObject_MockObject|DataStoresInterface
     */
    protected function createDataStoreEmptyMock($identifier = 'id')
    {
        $mockObject = $this->getMockBuilder(DataStoresInterface::class)->getMock();

        $mockObject->expects($this->any())->method('getIdentifier')->willReturn($identifier);

        return $mockObject;
    }

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
     * @param AbstractHandler $object
     */
    protected function assertDelegateCall(
        ResponseInterface $expectedResponse,
        ServerRequestInterface $request,
        AbstractHandler $object
    ) {
        $assertCallback = function (ServerRequestInterface $request) use ($expectedResponse) {
            $actualData = $request->getAttribute(JsonRenderer::RESPONSE_DATA) ?? [];
            $actualResponse = $request->getAttribute(ResponseInterface::class) ?? new Response();

            return $this->isResponseEquals($expectedResponse, $actualData, $actualResponse);
        };

        /** @var PHPUnit_Framework_MockObject_MockObject|DelegateInterface $mockDelegate */
        $mockDelegate = $this->getMockBuilder(DelegateInterface::class)->getMock();
        $mockDelegate->expects($this->once())->method('process')->with($this->callback($assertCallback));

        $object->process($request, $mockDelegate);
    }

    /**
     * @param ResponseInterface $expected
     * @param $actualData
     * @param ResponseInterface $actual
     * @return bool
     * @throws Exception
     */
    protected function isResponseEquals(ResponseInterface $expected, $actualData, ResponseInterface $actual)
    {
        if ($expected->getStatusCode() !== $actual->getStatusCode()) {
            throw new Exception(
                "Expected status code {$expected->getStatusCode()}" . " do not equals actual {$actual->getStatusCode()}"
            );
        }

        if ($expected->getHeaders() !== $actual->getHeaders()) {
            $expectedSerializedHeaders = json_encode($expected->getHeaders());
            $actualSerializedHeaders = json_encode($actual->getHeaders());

            throw new Exception(
                "Expected headers {$expectedSerializedHeaders}" . " do not equals actual {$actualSerializedHeaders}"
            );
        }

        $expectedStream = $expected->getBody()->getContents();

        if (unserialize($expectedStream) !== $actualData) {
            $expectedSerializedContents = json_encode(unserialize($expectedStream));
            $actualSerializedContents = json_encode($actualData);

            throw new Exception(
                "Expected contents {$expectedSerializedContents}" . " do not equals actual {$actualSerializedContents}"
            );
        }

        return true;
    }
}
