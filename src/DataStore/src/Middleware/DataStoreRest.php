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
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use rollun\datastore\Middleware\Handler;
use Zend\Stratigility\MiddlewarePipe;

/**
 * Create middleware pipe with 'REST method handlers'.
 * Each 'REST method handler' check if it can handle this request.
 *
 * Class DataStoreRest
 * @package rollun\datastore\Middleware
 */
class DataStoreRest implements MiddlewareInterface
{
    protected $middlewarePipe;

    /**
     * @var DataStoresInterface
     */
    private $dataStore;

    /**
     * DataStoreRest constructor.
     * @param DataStoresInterface $dataStore
     */
    public function __construct(DataStoresInterface $dataStore)
    {
        $this->middlewarePipe = new MiddlewarePipe();
        $this->dataStore = $dataStore;

        $this->middlewarePipe->pipe(new Handler\HeadHandler($this->dataStore));
        $this->middlewarePipe->pipe(new Handler\DownloadCsvHandler($this->dataStore));
        $this->middlewarePipe->pipe(new Handler\QueryHandler($this->dataStore));
        $this->middlewarePipe->pipe(new Handler\ReadHandler($this->dataStore));
        $this->middlewarePipe->pipe(new Handler\MultiCreateHandler($this->dataStore));
        $this->middlewarePipe->pipe(new Handler\CreateHandler($this->dataStore));
        $this->middlewarePipe->pipe(new Handler\UpdateHandler($this->dataStore));
        $this->middlewarePipe->pipe(new Handler\RefreshHandler($this->dataStore));
        $this->middlewarePipe->pipe(new Handler\DeleteHandler($this->dataStore));
        $this->middlewarePipe->pipe(new Handler\ErrorHandler());
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $this->middlewarePipe->process($request, $handler);

        return $response;
    }
}
