<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 15.05.17
 * Time: 17:48
 */

namespace rollun\datastore\DataStore\Traits;

use rollun\datastore\DataStore\DataStoreException;

class NoSupportDeleteAllTrait
{
    /**
     * @inheritdoc
     * @return int number of deleted items or null if object doesn't support it
     */
    public function deleteAll()
    {
        throw new DataStoreException("Method don't support.");
    }
}
