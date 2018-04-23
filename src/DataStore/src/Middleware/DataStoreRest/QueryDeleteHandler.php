<?php


namespace rollun\datastore\Middleware\DataStoreRest;


use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use rollun\actionrender\Renderer\AbstractRenderer;
use Xiag\Rql\Parser\Query;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;

class QueryDeleteHandler extends AbstractHandler
{
    /**
     * check if datastore rest middleware may handle this request
     * @param ServerRequestInterface $request
     * @return bool
     */
    protected function canHandle(ServerRequestInterface $request): bool
    {
        $canHandle = $request->getMethod() === "DELETE";

        $query = $request->getAttribute('rqlQueryObject');
        $canHandle = $canHandle && isset($query) && $query instanceof Query;

        $primaryKeyValue = $request->getAttribute('primaryKeyValue');
        $canHandle = $canHandle && is_null($primaryKeyValue);


        $primaryKeyValue = $request->getAttribute('primaryKeyValue');
        $canHandle = $canHandle && isset($primaryKeyValue);

        return $canHandle;
    }

    /**
     * Handle request to dataStore;
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Query $rqlQueryObject */
        $rqlQueryObject = $request->getAttribute('rqlQueryObject');

        $deletedItems = $this->dataStore->deleteByQuery($rqlQueryObject);

        $response = new Response();

        $stream = fopen("data://text/plain;base64,".base64_encode(serialize($deletedItems)), 'r');
        $response = $response->withBody(new Stream($stream));
        return $response;
    }
}