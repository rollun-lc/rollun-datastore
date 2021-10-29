<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use rollun\datastore\DataStore\Interfaces\DataStoreInterface;

/**
 * Abstract middleware with injected data store
 *
 * @category   rest
 * @package    zaboy
 */
abstract class DataStoreAbstract implements MiddlewareInterface
{
    /**
     * @var DataStoreInterface
     */
    protected $dataStore;

    /**
     * DataStoreAbstract constructor.
     *
     * @param DataStoreInterface $dataStore
     */
    public function __construct(DataStoreInterface $dataStore)
    {
        $this->dataStore = $dataStore;
    }
}
