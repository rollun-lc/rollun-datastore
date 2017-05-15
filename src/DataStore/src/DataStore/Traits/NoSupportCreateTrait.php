<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 15.05.17
 * Time: 17:46
 */

namespace rollun\datastore\DataStore\Traits;

use rollun\datastore\DataStore\DataStoreException;

trait NoSupportCreateTrait
{
    /**
     *{@inheritdoc}
     * @param array $itemData associated array with or without PrimaryKey ["id" => 1, "field name" = "foo" ]
     * @param bool $rewriteIfExist can item be rewrited if same 'id' exist
     * @return array created item or method will throw exception
     * @throws DataStoreException
     */
    public function create($itemData, $rewriteIfExist = false)
    {
        throw new DataStoreException("Method don't support.");
    }
}
