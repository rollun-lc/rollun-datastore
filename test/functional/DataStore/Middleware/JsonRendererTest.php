<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\functional\DataStore\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use rollun\datastore\Middleware\JsonRenderer;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequest;

class JsonRendererTest extends BaseMiddlewareTest
{
    public function testProcessSuccessWithoutResponseInAttribute()
    {
        $request = new ServerRequest();
        $response = new JsonResponse(null, 200, ['content-type' => 'application/json']);

        $object = new JsonRenderer();

        /** @var RequestHandlerInterface $delegateMock */
        $delegateMock = $this->createMock(RequestHandlerInterface::class);

        $this->assertJsonResponseEquals(
            $response,
            $object->process($request, $delegateMock)
        );
    }

    public function testProcessSuccessWithResponseInAttribute()
    {
        $headers = [
            'a' => 'b',
            'c' => 'b',
        ];
        $data = ['someData'];

        $response = new JsonResponse($data, 200, $headers);
        $request = new ServerRequest();
        $request = $request->withAttribute(ResponseInterface::class, $response);
        $request = $request->withAttribute(JsonRenderer::RESPONSE_DATA, $data);

        $object = new JsonRenderer();

        /** @var RequestHandlerInterface $delegateMock */
        $delegateMock = $this->createMock(RequestHandlerInterface::class);

        $this->assertJsonResponseEquals(
            $response,
            $object->process($request, $delegateMock)
        );
    }

    public function assertJsonResponseEquals(ResponseInterface $expected, ResponseInterface $actual)
    {
        $this->assertEquals(
            $expected->getBody()
                ->getContents(),
            $actual->getBody()
                ->getContents()
        );

        $this->assertEquals($expected->getHeaders(), $actual->getHeaders());
        $this->assertEquals($expected->getStatusCode(), $actual->getStatusCode());
    }
}
