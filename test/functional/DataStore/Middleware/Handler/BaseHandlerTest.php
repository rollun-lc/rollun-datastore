<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\functional\DataStore\Middleware\Handler;

use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use rollun\datastore\Middleware\JsonRenderer;
use rollun\test\functional\DataStore\Middleware\BaseMiddlewareTest;
use Zend\Diactoros\Response;

abstract class BaseHandlerTest extends BaseMiddlewareTest
{
    public function getAssertionCallback($expectedResponse)
    {
        return function (ServerRequestInterface $request) use ($expectedResponse) {
            $actualData = $request->getAttribute(JsonRenderer::RESPONSE_DATA) ?? [];
            $actualResponse = $request->getAttribute(ResponseInterface::class) ?? new Response();

            return $this->isResponseEquals($expectedResponse, $actualData, $actualResponse);
        };
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

        $expectedStream = $expected->getBody()
            ->getContents();

        if (unserialize($expectedStream) !== $actualData) {
            $expectedSerializedContents = json_encode($expectedStream);
            $actualSerializedContents = json_encode($actualData);

            throw new Exception(
                "Expected contents {$expectedSerializedContents}" . " do not equals actual {$actualSerializedContents}"
            );
        }

        return true;
    }
}
