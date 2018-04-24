<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @see http://tools.ietf.org/html/rfc2616#page-122
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace rollun\rest\Middleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use Zend\Stratigility\MiddlewarePipe;

/**
 * Send GET POST PUT DELETE PATCH request
 * @category   rest
 * @package    zaboy
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
        $this->pipe(new DataStoreRest\MultiplyCreateHandler($this->dataStore));
        $this->pipe(new DataStoreRest\UpdateHandler($this->dataStore));
        $this->pipe(new DataStoreRest\MultiplyUpdateHandler($this->dataStore));
        $this->pipe(new DataStoreRest\QueryUpdateHandler($this->dataStore));
        $this->pipe(new DataStoreRest\RefreshHandler($this->dataStore));
        $this->pipe(new DataStoreRest\DeleteHandler($this->dataStore));
        $this->pipe(new DataStoreRest\QueryDeleteHandler($this->dataStore));
        $this->pipe(new DataStoreRest\HtmlView());
    }
}
