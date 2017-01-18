<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 18.01.17
 * Time: 18:33
 */

namespace rollun\datastore\Viewer;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Stratigility\MiddlewareInterface;

class ViewerAction implements MiddlewareInterface
{

    /** @var ViewerInterface */
    protected $viewer;


    public function __construct(ViewerInterface $viewer)
    {
        $this->viewer = $viewer;
    }

    /**
     * Process an incoming request and/or response.
     *
     * Accepts a server-side request and a response instance, and does
     * something with them.
     *
     * If the response is not complete and/or further processing would not
     * interfere with the work done in the middleware, or if the middleware
     * wants to delegate to another process, it can use the `$out` callable
     * if present.
     *
     * If the middleware does not return a value, execution of the current
     * request is considered complete, and the response instance provided will
     * be considered the response to return.
     *
     * Alternately, the middleware may return a response instance.
     *
     * Often, middleware will `return $out();`, with the assumption that a
     * later middleware will return a response.
     *
     * @param Request $request
     * @param ResponseInterface $response
     * @param null|callable $out
     * @return null|ResponseInterface
     */
    public function __invoke(Request $request, ResponseInterface $response, callable $out = null)
    {
        /** TODO: add type of return (page or widget) */
        return new HtmlResponse($this->viewer->getPage());
    }
}
