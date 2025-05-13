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
use Psr\Log\LoggerInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\TextResponse;
use Laminas\Stratigility\Middleware\RequestHandlerMiddleware;
use Laminas\Stratigility\MiddlewarePipe;

class DataStoreApi implements MiddlewareInterface
{
    protected $middlewarePipe;

    /** @var LoggerInterface|null */
    protected $logger;

    /**
     * DataStoreApi constructor.
     * @param Determinator $determinator
     * @param RequestHandlerInterface|null $renderer
     */
    public function __construct(
        Determinator $determinator,
        RequestHandlerInterface $renderer = null,
        LoggerInterface $logger = null
    ) {
        $this->logger = $logger;

        $this->middlewarePipe = new MiddlewarePipe();

        $this->middlewarePipe->pipe(new ResourceResolver());
        $this->middlewarePipe->pipe(new RequestDecoder());
        $this->middlewarePipe->pipe($determinator);

        if ($renderer) {
            $renderer = new RequestHandlerMiddleware($renderer);
        } else {
            $renderer = new JsonRenderer();
        }

        $this->middlewarePipe->pipe($renderer);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $this->middlewarePipe->process($request, $handler);
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error("Exception in Datastore middleware", [
                    'exception' => $e,
                ]);
            }
            $accept = $request->getHeader('Accept');
            if (in_array('application/json', $accept)) {
                return new JsonResponse([
                    'error' => $e->getMessage(),
                ], 500);
            } else {
                return new TextResponse($e->getMessage(), 500);
            }
        }
    }
}
