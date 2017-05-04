<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace rollun\datastore\Middleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;


/**
 * Extracts resource name and row id from URL or from request attributes
 *
 * <b>Used request attributes: </b>
 * <ul>
 * <li>resourceName</li>
 * <li>primaryKeyValue</li>
 * </ul>
 * If URL is </br>'site,com/api/rest/RESOURCE-NAME/ROWS-ID'</br>
 * request->getAttribute('resourceName') returns 'RESOURCE-NAME'</br>
 * request->getAttribute('primaryKeyValue') returns 'ROWS-ID'</br>
 * </br>
 * If URL is </br>'site,com/restapi/RESOURCE-NAME?a=1&limit(2,5)'
 * request->getAttribute('resourceName') returns 'RESOURCE-NAME'</br>
 * request->getAttribute('primaryKeyValue') returns null</br>
 *
 * @category   rest
 * @package    zaboy
 */
class ResourceResolver implements MiddlewareInterface
{

    /**
     * Process an incoming server request and return a response, optionally delegating
     * to the next middleware component to create the response.
     *
     * @param ServerRequestInterface $request
     * @param DelegateInterface $delegate
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        if (null !== $request->getAttribute("resourceName")) {
            //Router have set "resourceName". It work in expressive.
            $id = empty($request->getAttribute("id")) ? null : $this->decodeString($request->getAttribute("id"));
            $request = $request->withAttribute('primaryKeyValue', $id);
        } else {
            //"resourceName" isn't set. It work in stratigility.
            $path = $request->getUri()->getPath();
            preg_match('/^[\/]?([\w\~\-\_]+)([\/]([-%_A-Za-z0-9]+))?/', $path, $matches);
            $resourceName = isset($matches[1]) ? $matches[1] : null;
            $request = $request->withAttribute('resourceName', $resourceName);

            $id = isset($matches[3]) ? $this->decodeString($matches[3]) : null;
            $request = $request->withAttribute('primaryKeyValue', $id);
        }

        $response = $delegate->process($request);

        return $response;
    }

    private function decodeString($value)
    {
        return rawurldecode(strtr($value, [
            '%2D' => '-',
            '%5F' => '_',
            '%2E' => '.',
            '%7E' => '~',
        ]));
    }
}
