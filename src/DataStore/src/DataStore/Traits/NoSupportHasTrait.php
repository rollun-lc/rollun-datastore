<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 15.05.17
 * Time: 17:48
 */

namespace rollun\datastore\DataStore\Traits;


use rollun\datastore\DataStore\DataStoreException;

trait NoSupportHasTrait
{
    /**
     * @inheritdoc
     * @param int|string $id PrimaryKey
     * @return bool
     * @throws DataStoreException
     */
    public function has($id)
    {
        throw new DataStoreException("Method don't support.");
    }
}