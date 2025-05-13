<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Traits;

use rollun\datastore\DataStore\DataStoreException;

/**
 * Use this trait to disable 'deleteAll' method in datastore
 *
 * Trait NoSupportDeleteAllTrait
 * @package rollun\datastore\DataStore\Traits
 */
trait NoSupportDeleteAllTrait
{
    /**
     * @throws DataStoreException
     */
    public function deleteAll()
    {
        trigger_error(NoSupportDeleteAllTrait::class . ' trait is deprecated', E_USER_DEPRECATED);

        throw new DataStoreException("Method don't support.");
    }
}
