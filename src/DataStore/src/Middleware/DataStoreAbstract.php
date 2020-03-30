<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use rollun\datastore\DataStore\Interfaces\DataStoreInterface;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;

/**
 * Abstract middleware with injected data store
 *
 * @category   rest
 * @package    zaboy
 */
abstract class DataStoreAbstract implements MiddlewareInterface
{
    /**
     * @var DataStoresInterface|DataStoreInterface
     */
    protected $dataStore;

    /**
     * DataStoreAbstract constructor.
     *
     * @param DataStoresInterface|DataStoreInterface $dataStore
     */
    public function __construct($dataStore)
    {
        if ($dataStore instanceof DataStoreInterface || $dataStore instanceof DataStoresInterface) {
            $this->dataStore = $dataStore;
        } else {
            throw new \InvalidArgumentException("DataStore '$dataStore' should be instance of DataStoreInterface or DataStoresInterface");
        }
    }
}
