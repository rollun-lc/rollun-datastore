<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 15.05.17
 * Time: 17:47
 */

namespace rollun\datastore\DataStore\Traits;

use rollun\datastore\DataStore\DataStoreException;

trait NoSupportReadTrait
{
    /**
     * {@inheritdoc}
     * @param int|string $id PrimaryKey
     * @return array|null
     * @throws DataStoreException
     */
    public function read($id)
    {
        throw new DataStoreException("Method don't support.");
    }
}
