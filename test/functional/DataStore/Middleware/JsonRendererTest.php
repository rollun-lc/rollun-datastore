<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\functional\DataStore\Middleware;

use Psr\Http\Message\ResponseInterface;
use rollun\datastore\Middleware\JsonRenderer;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Stream;

class JsonRendererTest extends BaseMiddlewareTest
{
    public function testProcessSuccessWithoutResponseInAttribute()
    {
        $request = new ServerRequest();
        $response = $this->createResponse(200, ['content-type' => 'application/json'], []);

        $object = new JsonRenderer();
        $this->assertDelegateCallWithResponseAssertion($response, $request, $object);
    }

    public function testProcessSuccessWithResponseInAttribute()
    {
        $headers = [
            'a' => 'b',
            'c' => 'b',
        ];
        $data = ['someData'];

        $response = $this->createResponse(300, array_merge(['content-type' => 'application/json'], $headers));
        $request = new ServerRequest();
        $request = $request->withAttribute(ResponseInterface::class, $response);
        $request = $request->withAttribute(JsonRenderer::RESPONSE_DATA, $data);

        $response = $response->withBody(new Stream("data://text/plain;base64," . base64_encode(serialize($data))));

        $object = new JsonRenderer();
        $this->assertDelegateCallWithResponseAssertion($response, $request, $object);
    }
}
