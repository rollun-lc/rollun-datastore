<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Traits;

use rollun\datastore\DataStore\DataStoreException;

/**
 * Use this trait to disable 'read' method in datastore
 *
 * Trait NoSupportReadTrait
 * @package rollun\datastore\DataStore\Traits
 */
trait NoSupportReadTrait
{
    /**
     * @param $id
     * @throws DataStoreException
     */
    public function read($id)
    {
        trigger_error(NoSupportReadTrait::class . ' trait is deprecated', E_USER_DEPRECATED);

        throw new DataStoreException("Method don't support.");
    }
}
