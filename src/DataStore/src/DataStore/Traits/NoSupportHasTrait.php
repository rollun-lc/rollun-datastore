<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Traits;

use rollun\datastore\DataStore\DataStoreException;

/**
 * Use this trait to disable 'has' method in datastore
 *
 * Trait NoSupportHasTrait
 * @package rollun\datastore\DataStore\Traits
 */
trait NoSupportHasTrait
{
    /**
     * @param $id
     * @throws DataStoreException
     */
    public function has($id)
    {
        trigger_error(NoSupportHasTrait::class . ' trait is deprecated', E_USER_DEPRECATED);

        throw new DataStoreException("Method don't support.");
    }
}
