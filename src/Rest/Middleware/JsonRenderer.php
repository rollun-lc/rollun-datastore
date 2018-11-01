<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\rest\Middleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

/**
 * Create json http response
 *
 * Class JsonRenderer
 * @package rollun\rest\Middleware
 */
class JsonRenderer implements MiddlewareInterface
{
    const RESPONSE_DATA = "responseData";

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $data = $request->getAttribute(static::RESPONSE_DATA);

        /** @var ResponseInterface $response */
        $response = $request->getAttribute(ResponseInterface::class) ?: null;

        if (!isset($response)) {
            $status = 200;
            $headers = [];
        } else {
            $status = $response->getStatusCode();
            $headers = $response->getHeaders();
        }

        $response = new JsonResponse($data, $status);

        foreach ($headers as $header => $value) {
            $response = $response->withHeader($header, $value);
        }

        $request = $request->withAttribute(ResponseInterface::class, $response);

        $response = $delegate->process($request);

        return $response;
    }
}
