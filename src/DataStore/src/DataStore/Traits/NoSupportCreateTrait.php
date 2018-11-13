<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Traits;

use rollun\datastore\DataStore\DataStoreException;

/**
 * Use this trait to disable 'create' method in datastore
 *
 * Trait NoSupportCreateTrait
 * @package rollun\datastore\DataStore\Traits
 */
trait NoSupportCreateTrait
{
    /**
     * @param $itemData
     * @param bool $rewriteIfExist
     * @throws DataStoreException
     */
    public function create($itemData, $rewriteIfExist = false)
    {
        trigger_error(NoSupportCreateTrait::class . ' trait is deprecated', E_USER_DEPRECATED);

        throw new DataStoreException("Method don't support.");
    }
}
