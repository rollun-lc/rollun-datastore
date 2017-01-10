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
use rolluncom\datastore\RestException;
use Zend\Stratigility\MiddlewareInterface;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Escaper\Escaper;

/**
 * Check Accept Header and encode Response to JSON
 *
 * Encode Response from $request->getAttribute('Response-Body')
 *
 * @category   rest
 * @package    zaboy
 */
class ResponseEncoder implements MiddlewareInterface
{

    /**
     *
     * @todo Chenge format of JSON response from [{}] to {} for one row response?
     * @todo Add develope mode for debug with HTML POST and GET
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable|null $next
     * @return ResponseInterface
     * @throws \zaboy\rest\RestException
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next = null)
    {
        $responseBody = $request->getAttribute('Response-Body');
        $accept = $request->getHeaderLine('Accept');
        if (isset($accept) && preg_match('#^application/([^+\s]+\+)?json#', $accept)) {
            $status = $response->getStatusCode();
            $headers = $response->getHeaders();
            $response = new JsonResponse($responseBody, $status, $headers);
        } else {
            $escaper = new Escaper();
            $result = '';
            switch (true) {
                case gettype($responseBody) == 'array' :
//                    foreach ($responseBody as $valueArray) {
//                        $result = $result . ' - ';
//                        if (is_array($valueArray)) {
//                            foreach ($valueArray as $key => $value) {
//                                $result = $result
//                                        . $escaper->escapeHtml($key)
//                                        . ' - '
//                                        . $escaper->escapeHtml(is_array($value) ? print_r($value, true) : $value)
//                                        . '; _   _  ';
//                            }
//                            $result = $result . '<br>' . PHP_EOL;
//                        } else {
//                            $result = $result . $escaper->escapeHtml($valueArray) . '<br>' . PHP_EOL;
//                        }
//                    }

                    $result = '<pre>' . $escaper->escapeHtml(print_r($responseBody, true)) . '</pre>';
                    break;
                case is_numeric($responseBody) or is_string($responseBody) :
                    $result = $responseBody . '<br>' . PHP_EOL;
                    break;
                case is_bool($responseBody) :
                    $result = $responseBody ? 'TRUE' : 'FALSE';
                    $result = $result . '<br>' . PHP_EOL;
                    break;
                default:
                    throw new RestException(
                    '$responseBody must be array, numeric or bool. But '
                    . gettype($responseBody) . ' given.'
                    );
            }
            $response->getBody()->write($result);
        }

        if ($next) {
            return $next($request, $response);
        }

        return $response;
    }

}
