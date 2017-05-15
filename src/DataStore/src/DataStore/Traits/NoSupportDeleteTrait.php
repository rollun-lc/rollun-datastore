<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 15.05.17
 * Time: 17:47
 */

namespace rollun\datastore\DataStore\Traits;

use rollun\datastore\DataStore\DataStoreException;

class NoSupportDeleteTrait
{
    /**
     * @inheritdoc
     * @param int|string $id PrimaryKey
     * @return array from elements or null is not support
     * @throws DataStoreException
     */
    public function delete($id)
    {
        throw new DataStoreException("Method don't support.");
    }
}
