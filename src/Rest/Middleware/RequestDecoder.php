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
use rollun\utils\Json\Serializer;
use rollun\rest\RestException;
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
 * @category   rest
 * @package    zaboy
 */
class RequestDecoder implements MiddlewareInterface
{
    /**
     * Process an incoming server request and return a response.
     * Optionally delegating to the next middleware component to create the response.
     *
     * @param ServerRequestInterface $request
     * @param DelegateInterface $delegate
     * @return ResponseInterface
     * @throws RestException
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        // Parse 'overwriteMode' attribute
        /* @see https://github.com/SitePen/dstore/blob/21129125823a29c6c18533e7b5a31432cf6e5c56/src/Rest.js */
        $overwriteModeHeader = $request->getHeader('If-Match');
        $overwriteMode = isset($overwriteModeHeader[0]) && $overwriteModeHeader[0] === '*' ? true : false;
        $request = $request->withAttribute('overwriteMode', $overwriteMode);

        // Parse 'putDefaultPosition' attribute
        $putDefaultPosition = $request->getHeader('Put-Default-Position'); // 'start', 'end'

        if (isset($putDefaultPosition)) {
            $request = $request->withAttribute('putDefaultPosition', $putDefaultPosition);
        }

        // Parse 'putBefore' attribute
        /* @see https://github.com/SitePen/dstore/issues/42 */
        $putBeforeHeader = $request->getHeader('Put-Before');
        $putBefore = !empty($putBeforeHeader);
        $request = $request->withAttribute('putBefore', $putBefore);

        $rqlQueryStringWithXdebug = $request->getUri()->getQuery();

        // Trim XDEBUG query params for PHPStorm or NetBeans
        // $rqlQueryString = rtrim($rqlQueryStringWithXdebug, '&XDEBUG_SESSION_START=netbeans-xdebug');
        $rqlQueryString = preg_replace('/\&XDEBUG_SESSION_START\=[\w\d_-]+/', "", $rqlQueryStringWithXdebug);

        // Parse 'rqlQueryObject' attribute
        $rqlQueryObject = RqlParser::rqlDecode($rqlQueryString);
        $request = $request->withAttribute('rqlQueryObject', $rqlQueryObject);

        // Parse 'Limit' attribute
        $headerLimit = $request->getHeader('Range');

        if (isset($headerLimit) && is_array($headerLimit) && count($headerLimit) > 0) {
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
                $request = $request->withAttribute("Limit", $limit);
            }
        }

        // Parse body
        $contentTypeArray = $request->getHeader('Content-Type');
        $contentType = isset($contentTypeArray[0]) ? $contentTypeArray[0] : 'text/html';

        if (false !== strpos($contentType, 'json')) {
            $body = !empty($request->getBody()->__toString())
                ? Serializer::jsonUnserialize($request->getBody()->__toString())
                : null;

            $request = $request->withParsedBody($body);
        } elseif ($contentType === 'text/plain'
            or $contentType === 'text/html'
            or $contentType === 'application/x-www-form-urlencoded'
        ) {
            // TODO: Deprecated
            trigger_error("Request body should be only json decoded", E_USER_DEPRECATED);

            $body = !empty($request->getBody()->__toString())
                ? $request->getBody()->__toString()
                : null;

            $request = $request->withParsedBody($body);
        } else {
            throw new RestException("Unknown Content-Type header - $contentType");
        }

        $response = $delegate->process($request);

        return $response;
    }
}
