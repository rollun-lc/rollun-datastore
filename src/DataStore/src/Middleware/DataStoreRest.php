<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Middleware;

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
class DataStoreRest extends MiddlewarePipe
{
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
        parent::__construct();
        $this->dataStore = $dataStore;

        $this->pipe(new Handler\QueryHandler($this->dataStore));
        $this->pipe(new Handler\ReadHandler($this->dataStore));
        $this->pipe(new Handler\CreateHandler($this->dataStore));
        $this->pipe(new Handler\UpdateHandler($this->dataStore));
        $this->pipe(new Handler\RefreshHandler($this->dataStore));
        $this->pipe(new Handler\DeleteHandler($this->dataStore));
        $this->pipe(new Handler\ErrorHandler());
    }
}
