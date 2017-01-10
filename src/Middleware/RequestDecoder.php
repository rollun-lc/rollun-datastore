<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace rolluncom\datastore\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Xiag\Rql\Parser\TokenParser;
use Xiag\Rql\Parser\TokenParser\Query;
use Xiag\Rql\Parser\TypeCaster;
use rolluncom\datastore\RestException;
use rolluncom\datastore\Rql\RqlParser;
use Zend\Stratigility\MiddlewareInterface;

/**
 * Parse body fron JSON and add result array to $request->withParsedBody()
 *
 * <b>Used request attributes: </b>
 * <ul>
 * <li>Overwrite-Mode</li>
 * <li>Put-Default-Position</li>
 * <li>Put-Before</li>
 * <li>Rql-Query-Object</li>*
 * </ul>
 *
 * @category   rest
 * @package    zaboy
 */
class RequestDecoder implements MiddlewareInterface
{

    private $allowedAggregateFunction = ['count', 'max', 'min'];

    /**                         Location: http://www.example.com/users/4/
     *
     * @todo positionHeaders = 'beforeId'  'Put-Default-Position'  'Put-Default-Position'
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable|null $next
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next = null)
    {

        // @see https://github.com/SitePen/dstore/blob/21129125823a29c6c18533e7b5a31432cf6e5c56/src/Rest.js
        $overwriteModeHeader = $request->getHeader('If-Match');
        $overwriteMode = isset($overwriteModeHeader[0]) && $overwriteModeHeader[0] === '*' ? true : false;
        $request = $request->withAttribute('Overwrite-Mode', $overwriteMode);

        $putDefaultPosition = $request->getHeader('Put-Default-Position'); //'start' : 'end'
        if (isset($putDefaultPosition)) {
            $request = $request->withAttribute('Put-Default-Position', $putDefaultPosition);
        }
        // @see https://github.com/SitePen/dstore/issues/42
        $putBeforeHeader = $request->getHeader('Put-Before');
        $putBefore = !empty($putBeforeHeader);
        $request = $request->withAttribute('Put-Before', $putBefore);

        $rqlQueryStringWithXdebug = $request->getUri()->getQuery();

        $rqlQueryString = rtrim($rqlQueryStringWithXdebug, '&XDEBUG_SESSION_START=netbeans-xdebug');
        $rqlQueryObject = RqlParser::rqlDecode($rqlQueryString);
        $request = $request->withAttribute('Rql-Query-Object', $rqlQueryObject);

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

        $contenttypeArray = $request->getHeader('Content-Type');
        $contenttype = isset($contenttypeArray[0]) ? $contenttypeArray[0] : 'text/html';

        if (false !== strpos($contenttype, 'json')) {
            $body = !empty($request->getBody()->__toString()) ? $this->jsonDecode($request->getBody()->__toString()) : null;
            $request = $request->withParsedBody($body);
        } elseif ($contenttype === 'text/plain'
            or $contenttype === 'text/html'
            or $contenttype === 'application/x-www-form-urlencoded'
        ) {
            $body = !empty($request->getBody()->__toString()) ? $request->getBody()->__toString() : null;
            $request = $request->withParsedBody($body);
        } else {
            //todo XML?
            throw new RestException(
                'Unknown Content-Type header - ' .
                $contenttype
            );
        }

        if ($next) {
            return $next($request, $response);
        }

        return $response;
    }

    protected function jsonDecode($data)
    {
        // Clear json_last_error()
        json_encode(null);
        $result = json_decode($data, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new RestException(
                'Unable to decode data from JSON' .
                json_last_error_msg()
            );
        }
        json_encode(null);

        return $result;
    }

}
