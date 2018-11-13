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
     * @return array Return data of DataSource
     */
    public function getAll();
}
