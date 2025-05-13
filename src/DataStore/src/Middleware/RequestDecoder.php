<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use rollun\utils\Json\Serializer;
use rollun\datastore\Rql\RqlParser;

/**
 * Parse body from JSON and add result array to $request->withParsedBody()
 * Also parse attributes:
 * - overwriteMode
 * - putDefaultPosition
 * - putBefore
 * - rqlQueryObject ($request->getAttribute('rqlQueryObject') returns Query object)
 * - Limit
 *
 * Class RequestDecoder
 * @package rollun\datastore\Middleware
 */
class RequestDecoder implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = $this->parseOverwriteMode($request);
        $request = $this->parseRqlQuery($request);
        $request = $this->parseHeaderLimit($request);
        $request = $this->parseRequestBody($request);
        $request = $this->parseContentRange($request);

        $response = $handler->handle($request);

        return $response;
    }

    protected function parseContentRange(ServerRequestInterface $request)
    {
        $withContentRangeHeader = $request->getHeader('With-Content-Range');
        $withContentRange = (isset($withContentRangeHeader[0]) && $withContentRangeHeader[0] === '*')
            ? true
            : false;

        $request = $request->withAttribute('withContentRange', $withContentRange);

        return $request;
    }

    /**
     * @see https://github.com/SitePen/dstore/blob/21129125823a29c6c18533e7b5a31432cf6e5c56/src/Rest.js
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     */
    protected function parseOverwriteMode(ServerRequestInterface $request)
    {
        $overwriteModeHeader = $request->getHeader('If-Match');
        $overwriteMode = (isset($overwriteModeHeader[0]) && $overwriteModeHeader[0] === '*')
            ? true
            : false;

        $request = $request->withAttribute('overwriteMode', $overwriteMode);

        return $request;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     */
    protected function parseRqlQuery(ServerRequestInterface $request)
    {
        $rqlQueryStringWithXdebug = $request->getUri()
            ->getQuery();

        // Trim XDEBUG query params for PHPStorm or NetBeans
        // $rqlQueryString = rtrim($rqlQueryStringWithXdebug, '&XDEBUG_SESSION_START=netbeans-xdebug');
        $rqlQueryString = preg_replace(
            '/\&XDEBUG_SESSION_START\=[\w\d_-]+/',
            "",
            $rqlQueryStringWithXdebug
        );

        $rqlQueryObject = RqlParser::rqlDecode($rqlQueryString);
        return $request->withAttribute('rqlQueryObject', $rqlQueryObject);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     */
    protected function parseHeaderLimit(ServerRequestInterface $request)
    {
        $headerLimit = $request->getHeader('Range');

        if (isset($headerLimit) && is_array($headerLimit) && count($headerLimit) > 0) {
            trigger_error("Header 'Range' is deprecated", E_USER_DEPRECATED);

            $match = [];
            preg_match('/^items=([0-9]+)\-?([0-9]+)?/', $headerLimit[0], $match);

            if (count($match) > 0) {
                $limit = [];

                if (isset($match[2])) {
                    $limit['offset'] = $match[1];
                    $limit['limit'] = $match[2];
                } else {
                    $limit['limit'] = $match[1];
                }

                $request = $request->withAttribute('Limit', $limit);
            }
        }

        return $request;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     */
    protected function parseRequestBody(ServerRequestInterface $request)
    {
        $contentTypeArray = $request->getHeader('Content-Type');
        $contentType = $contentTypeArray[0] ?? 'text/html';

        if (str_contains($contentType, 'json')) {
            $body = !empty($request->getBody()->__toString())
                ? Serializer::jsonUnserialize($request->getBody()->__toString())
                : null;

            $request = $request->withParsedBody($body);
        } elseif ($contentType === 'text/plain'
            or $contentType === 'text/html'
            or $contentType === 'application/x-www-form-urlencoded'
        ) {
            $request = $request->withParsedBody(null);
        } else {
            throw new RestException("Unknown Content-Type header - $contentType");
        }

        return $request;
    }
}
