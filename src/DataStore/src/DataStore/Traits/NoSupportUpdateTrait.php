<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Traits;

use rollun\datastore\DataStore\DataStoreException;

/**
 * Use this trait to disable 'update' method in datastore
 *
 * Trait NoSupportUpdateTrait
 * @package rollun\datastore\DataStore\Traits
 */
trait NoSupportUpdateTrait
{
    /**
     * @param $itemData
     * @param bool $createIfAbsent
     * @throws DataStoreException
     */
    public function update($itemData, $createIfAbsent = false)
    {
        trigger_error(NoSupportUpdateTrait::class . ' trait is deprecated', E_USER_DEPRECATED);

        throw new DataStoreException("Method don't support.");
    }
}
