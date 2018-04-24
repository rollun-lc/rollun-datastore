<?php


namespace rollun\rest\Middleware\DataStoreRest;


use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use rollun\actionrender\Renderer\AbstractRenderer;
use rollun\datastore\RestException;
use Xiag\Rql\Parser\Query;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;

class QueryUpdateHandler extends AbstractHandler
{

    /**
     * check if datastore rest middleware may handle this request
     * @param ServerRequestInterface $request
     * @return bool
     */
    protected function canHandle(ServerRequestInterface $request): bool
    {
        $canHandle = $request->getMethod() === "PUT";
        $query = $request->getAttribute('rqlQueryObject');
        $primaryKeyValue = $request->getAttribute('primaryKeyValue');
        $canHandle = $canHandle && isset($query) && $query instanceof Query && is_null($primaryKeyValue);

        $row = $request->getParsedBody();
        $canHandle = $canHandle && isset($row) && is_array($row) && array_reduce(array_keys($row), function ($carry, $item) {
                return $carry && !is_integer($item);
            }, true);
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

        $updatedRow = $request->getParsedBody();

        $updatedItems = $this->dataStore->updateByQuery($rqlQueryObject, $updatedRow);

        $response = new Response();


        $stream = fopen("data://text/plain;base64,".base64_encode(serialize($updatedItems)), 'r');
        $response = $response->withBody(new Stream($stream));

        return $response;
    }
}