<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @see       http://github.com/zendframework/zend-stratigility for the canonical source repository
 * @copyright Copyright (c) 2015-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-stratigility/blob/master/LICENSE.md New BSD License
 */

namespace rollun\datastore\Pipe;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Zend\Stratigility\FinalHandler;
use Zend\Stratigility\Next;
use Zend\Stratigility\MiddlewarePipe;
use Zend\Stratigility\Http;

/**
 * Pipe supported options
 *
 * <code>
 * use rollun\datastore\Pipe\MiddlewarePipeOptions;
 *
 * $app = new MiddlewarePipeOptions(['env' => 'develop']); //for show errors in browser
 * $app->pipe('/api', ...
 *
 * $app = new MiddlewarePipeOptions([]); //for not
 * $app->pipe('/api', ...
 * <code>
 *
 * @category   rest
 * @package    zaboy
 */
class MiddlewarePipeOptions extends MiddlewarePipe
{

    /**
     * @var array
     */
    protected $finalHandlerOptions;

    /**
     * Constructor
     *
     */
    public function __construct($finalHandlerOptions = [])
    {
        parent::__construct();
        $this->finalHandlerOptions = $finalHandlerOptions;
    }

    /**
     * Handle a request
     *
     * Takes the pipeline, creates a Next handler, and delegates to the
     * Next handler.
     *
     * If $out is a callable, it is used as the "final handler" when
     * $next has exhausted the pipeline; otherwise, a FinalHandler instance
     * is created and passed to $next during initialization.
     *
     * @param Request $request
     * @param Response $response
     * @param callable $out
     * @return Response
     */
    public function __invoke(Request $request, Response $response, callable $out = null)
    {
        $request = $this->decorateRequest($request);
        $response = $this->decorateResponse($response);

        $done = $out ? : new FinalHandler($this->finalHandlerOptions, $response);
        $next = new Next($this->pipeline, $done);
        $result = $next($request, $response);

        return ($result instanceof Response ? $result : $response);
    }

    /**
     * Decorate the Request instance
     *
     * @param Request $request
     * @return Http\Request
     */
    protected function decorateRequest(Request $request)
    {
        if ($request instanceof Http\Request) {
            return $request;
        }

        return new Http\Request($request);
    }

    /**
     * Decorate the Response instance
     *
     * @param Response $response
     * @return Http\Response
     */
    protected function decorateResponse(Response $response)
    {
        if ($response instanceof Http\Response) {
            return $response;
        }

        return new Http\Response($response);
    }

}
