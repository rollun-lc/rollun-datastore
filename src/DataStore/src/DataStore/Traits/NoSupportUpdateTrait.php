<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 15.05.17
 * Time: 17:47
 */

namespace rollun\datastore\DataStore\Traits;

use rollun\datastore\DataStore\DataStoreException;

class NoSupportUpdateTrait
{
    /**
     *{@inheritdoc}
     * @param array $itemData associated array with PrimaryKey ["id" => 1, "field name" = "foo" ]
     * @param bool $createIfAbsent can item be created if same 'id' is absent in the store
     * @return array updated or inserted item.
     * @throws DataStoreException
     */
    public function update($itemData, $createIfAbsent = false)
    {
        throw new DataStoreException("Method don't support.");
    }
}
