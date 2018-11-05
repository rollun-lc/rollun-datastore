<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Middleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Extracts resource name and row id from URL or from request attributes
 *
 * Used request attributes:
 * - resourceName (data store service name)
 * - primaryKeyValue (primary key value to fetch record for record)
 *
 * Examples:
 *
 * - if URL is http://example.com/api/datastore/RESOURCE-NAME/ROW-ID
 *  $request->getAttribute('resourceName') returns 'RESOURCE-NAME'
 *  $request->getAttribute('primaryKeyValue') returns 'ROW-ID'
 *
 * - if URL is http://example.com/api/datastore/RESOURCE-NAME?eq(a,1)&limit(2,5)
 *  $request->getAttribute('resourceName') returns 'RESOURCE-NAME
 *  $request->getAttribute('primaryKeyValue') returns null
 *
 * Class ResourceResolver
 * @package rollun\datastore\Middleware
 */
class ResourceResolver implements MiddlewareInterface
{
    const BASE_PATH = '/api/datastore';

    /**
     * @var string
     */
    protected $basePath;

    public function __construct($basePath = null)
    {
        $this->basePath = $basePath ?? self::BASE_PATH;
    }

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
        if ($request->getAttribute("resourceName") !== null) {
            // Router have set "resourceName". It work in expressive.
            $id = empty($request->getAttribute("id")) ? null : $this->decodeString($request->getAttribute("id"));
            $request = $request->withAttribute('primaryKeyValue', $id);
        } else {
            // "resourceName" isn't set. It work in stratigility.
            $path = $request->getUri()->getPath();
            $basePath = preg_quote(rtrim($this->basePath,'/'), '/');
            $pattern = "/{$basePath}\/([\w\~\-\_]+)([\/]([-%_A-Za-z0-9]+))?\/?$/";
            preg_match($pattern, $path, $matches);

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
        return rawurldecode(
            strtr(
                $value,
                [
                    '%2D' => '-',
                    '%5F' => '_',
                    '%2E' => '.',
                    '%7E' => '~',
                ]
            )
        );
    }
}
