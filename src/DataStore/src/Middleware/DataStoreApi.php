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
     * Main enter point for data store api pipeline
     *
     * DataStoreApi constructor.
     */
    public function __construct()
    {
        $this->pipe(ResourceResolver::class);
        $this->pipe(RequestDecoder::class);
        $this->pipe(DataStoreRest::class);
        $this->pipe(JsonRenderer::class);
    }
}
