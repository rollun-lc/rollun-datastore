<?php


namespace rollun\rest\Middleware\DataStoreRest;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use rollun\actionrender\Renderer\AbstractRenderer;
use rollun\rest\Middleware\DataStoreAbstract;

abstract class AbstractHandler extends DataStoreAbstract
{
    /**
     * check if datastore rest middleware may handle this request
     * @param ServerRequestInterface $request
     * @return bool
     */
    abstract protected function canHandle(ServerRequestInterface $request): bool;

    /**
     * Handle request to dataStore;
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    abstract protected function handle(ServerRequestInterface $request): ResponseInterface;

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
        if($this->canHandle($request)) {
            $response = $this->handle($request);
            $stream = $response->getBody();
            if(isset($stream)) {
                $data = unserialize($stream->getContents());
                $request = $request->withAttribute(AbstractRenderer::RESPONSE_DATA, $data);
            }
            $request = $request->withAttribute(ResponseInterface::class, $response);
        }

        $response = $delegate->process($request);
        return $response;
    }
}