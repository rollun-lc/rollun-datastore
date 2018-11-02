<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Middleware;

use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
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

        $this->pipe(new DataStoreRest\Validator());
        $this->pipe(new DataStoreRest\QueryHandler($this->dataStore));
        $this->pipe(new DataStoreRest\ReadHandler($this->dataStore));
        $this->pipe(new DataStoreRest\CreateHandler($this->dataStore));
        $this->pipe(new DataStoreRest\UpdateHandler($this->dataStore));
        $this->pipe(new DataStoreRest\RefreshHandler($this->dataStore));
        $this->pipe(new DataStoreRest\DeleteHandler($this->dataStore));
        $this->pipe(new DataStoreRest\HtmlView());
    }
}
