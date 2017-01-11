<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace rollun\datastore\Examples\Middleware;

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
     * Make DataStoreMemory
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable|null $next
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next = null)
    {
        $request = $request->withAttribute('memoryStore', $this->dataStore);

        if ($next) {
            return $next($request, $response);
        }
        return $response;
    }

}
