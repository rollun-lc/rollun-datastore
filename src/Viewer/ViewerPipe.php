<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 18.01.17
 * Time: 17:48
 */

namespace rollun\datastore\Viewer;

use Zend\Stratigility\MiddlewarePipe;

class ViewerPipe extends MiddlewarePipe
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
