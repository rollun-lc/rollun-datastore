<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Stratigility\MiddlewarePipe;

class DataStoreApi implements MiddlewareInterface
{
    protected $middlewarePipe;

    /**
     * DataStoreApi constructor.
     * @param Determinator $dataStoreDeterminator
     */
    public function __construct(Determinator $dataStoreDeterminator)
    {
        $this->middlewarePipe = new MiddlewarePipe();

        $this->middlewarePipe->pipe(new ResourceResolver());
        $this->middlewarePipe->pipe(new RequestDecoder());
        $this->middlewarePipe->pipe($dataStoreDeterminator);
        $this->middlewarePipe->pipe(new JsonRenderer());
    }

    public function process(Request $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $this->middlewarePipe->process($request, $handler);
        } catch (\Exception $e) {
            return new JsonResponse($e->getMessage(), 500);
        }
    }
}
