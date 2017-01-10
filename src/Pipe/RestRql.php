<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace rolluncom\datastore\Pipe;

use Zend\Stratigility\MiddlewarePipe;

/**
 * Pipe for execute REST calls
 *
 * @category   rest
 * @package    zaboy
 *
 */
class RestRql extends MiddlewarePipe
{

    /**
     *
     * @param array $middlewares
     */
    public function __construct($middlewares)
    {
        parent::__construct();
        foreach ($middlewares as $middleware) {
            $this->pipe($middleware);
        }
    }

}
