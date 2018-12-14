<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Interfaces;

use rollun\datastore\DataStore\DataStoreException;

/**
 * Interface RefreshableInterface
 * @package rollun\datastore\DataStore\Interfaces
 */
interface RefreshableInterface
{
    /**
     * @return null
     * @throws DataStoreException
     */
    public function refresh();
}
