<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\functional\DataStore\Middleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ResponseInterface;
use rollun\datastore\Middleware\JsonRenderer;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

class JsonRendererTest extends BaseMiddlewareTest
{
    public function testProcessSuccessWithoutResponseInAttribute()
    {
        $request = new ServerRequest();
        $response = new JsonResponse('', 200, ['content-type' => 'application/json']);

        $object = new JsonRenderer();

        /** @var DelegateInterface $delegateMock */
        $delegateMock = $this->createMock(DelegateInterface::class);

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

        /** @var DelegateInterface $delegateMock */
        $delegateMock = $this->createMock(DelegateInterface::class);

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
