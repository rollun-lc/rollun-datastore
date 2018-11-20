<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Interfaces;

/**
 * Interface DataSourceInterface
 * @package rollun\datastore\DataStore\Interfaces
 */
interface DataSourceInterface
{
    /**
     * Return some data that we can iterate
     *
     * @return \Traversable|array
     */
    public function getAll();
}
