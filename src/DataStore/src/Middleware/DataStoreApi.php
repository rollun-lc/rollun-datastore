<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Middleware;

use Zend\Stratigility\MiddlewarePipe;

class DataStoreApi extends MiddlewarePipe
{
    /**
     * DataStoreApi constructor.
     * @param Determinator $dataStoreDeterminator
     */
    public function __construct(Determinator $dataStoreDeterminator)
    {
        parent::__construct();

        $this->pipe(new ResourceResolver());
        $this->pipe(new RequestDecoder());
        $this->pipe($dataStoreDeterminator);
        $this->pipe(new JsonRenderer());
    }
}
