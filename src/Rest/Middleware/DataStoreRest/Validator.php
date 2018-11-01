<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\rest\Middleware\DataStoreRest;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use rollun\rest\RestException;

/**
 * Rise exception if request is not valid.
 * Class Validator
 * @package rollun\rest\Middleware\DataStoreRest
 */
class Validator implements MiddlewareInterface
{
    /**
     * Process an incoming server request and return a response.
     * Optionally delegating to the next middleware component to create the response.
     *
     * @param ServerRequestInterface $request
     * @param DelegateInterface $delegate
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $body = $request->getParsedBody();
        $primaryKeyValue = $request->getAttribute('primaryKeyValue');
        $rqlQueryObject = $request->getAttribute('rqlQueryObject');

        switch ($request->getMethod()) {
            case "POST":
                if (!isset($body) && !is_array($body)) {
                    throw new RestException('No body in POST request');
                }

                break;
            case "PUT":
                if (!isset($body) && !is_array($body)) {
                    throw new RestException('No body in PUT request');
                }

                if (!isset($primaryKeyValue) && !isset($rqlQueryObject)) {
                    throw new RestException('No query or id in PUT request');
                }

                break;
            case "GET":
                if (!isset($primaryKeyValue) && !isset($rqlQueryObject)) {
                    throw new RestException('No query or id in GET request');
                }

                break;
            case "DELETE":
                if (!isset($primaryKeyValue) && !isset($rqlQueryObject)) {
                    throw new RestException('No query or id in DELETE request');
                }

                break;
            case "PATCH":
                break;
            default:
                throw new RestException(
                    'Method must be GET, PUT, POST, PATCH or DELETE. ' . $request->getMethod() . ' given'
                );
        }
        $response = $delegate->process($request);

        return $response;
    }
}
