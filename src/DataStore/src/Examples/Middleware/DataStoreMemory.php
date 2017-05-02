<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace rollun\datastore\Examples\Middleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use rollun\datastore\Middleware;
use rollun\datastore\DataStore;

/**
 * Middleware which contane Memory Store
 *
 * Add Memory store to Request
 *
 * @category   rest
 * @package    zaboy
 */
class DataStoreMemory extends Middleware\DataStoreAbstract
{

    public function __construct(DataStore\Memory $dataStore = null)
    {
        if (empty($dataStore)) {
            $dataStore = new DataStore\Memory();
        }
        parent::__construct($dataStore);
    }

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
        $request = $request->withAttribute('memoryStore', $this->dataStore);
        $response = $delegate->process($request);
        return $response;
    }
}
