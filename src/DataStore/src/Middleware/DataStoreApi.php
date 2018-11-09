<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Webimpress\HttpMiddlewareCompatibility\HandlerInterface as DelegateInterface;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Stratigility\MiddlewarePipe;

class DataStoreApi extends MiddlewarePipe
{
    /**
     * DataStoreApi constructor.
     * @param Determinator $dataStoreDeterminator
     */
    public function __construct(Determinator $dataStoreDeterminator)
    {
        parent::__construct();

        $this->pipe(new ResourceResolver());
        $this->pipe(new RequestDecoder());
        $this->pipe($dataStoreDeterminator);
        $this->pipe(new JsonRenderer());
    }

    public function process(Request $request, DelegateInterface $delegate)
    {
        try {
            return parent::process($request, $delegate);
        } catch (\Exception $e) {
            return new JsonResponse($e->getMessage(), 500);
        }
    }
}
