<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Traits;

use rollun\datastore\DataStore\DataStoreException;

/**
 * Use this trait to disable 'delete' method in datastore
 *
 * Trait NoSupportDeleteTrait
 * @package rollun\datastore\DataStore\Traits
 */
trait NoSupportDeleteTrait
{
    /**
     * @throws DataStoreException
     */
    public function delete($id)
    {
        trigger_error(NoSupportDeleteTrait::class . ' trait is deprecated', E_USER_DEPRECATED);

        throw new DataStoreException("Method don't support.");
    }
}
