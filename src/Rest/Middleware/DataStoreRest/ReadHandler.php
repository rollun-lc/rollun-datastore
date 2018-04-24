<?php


namespace rollun\rest\Middleware\DataStoreRest;


use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use rollun\actionrender\Renderer\AbstractRenderer;
use rollun\actionrender\Renderer\Html\HtmlParamResolver;
use Xiag\Rql\Parser\Query;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;

class ReadHandler extends AbstractHandler
{
    /**
     * check if datastore rest middleware may handle this request
     * @param ServerRequestInterface $request
     * @return bool
     */
    protected function canHandle(ServerRequestInterface $request): bool
    {
        $canHandle = $request->getMethod() === "GET";

        $primaryKeyValue = $request->getAttribute('primaryKeyValue');
        $canHandle = $canHandle && isset($primaryKeyValue);

        /** @var Query $query */
        $query = $request->getAttribute('rqlQueryObject');
        $canHandle = $canHandle && (
                is_null($query) || (
                    is_null($query->getLimit()) &&
                    is_null($query->getSort()) &&
                    is_null($query->getSelect()) &&
                    is_null($query->getQuery())
                )
            );


        return $canHandle;
    }

    /**
     * Handle request to dataStore;
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        $primaryKeyValue = $request->getAttribute('primaryKeyValue');
        $items = $this->dataStore->read($primaryKeyValue);

        $response = new Response();

        $stream = fopen("data://text/plain;base64," . base64_encode(serialize($items)), 'r');
        $response = $response->withBody(new Stream($stream));

        return $response;
    }
}