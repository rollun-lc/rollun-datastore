<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 15.05.17
 * Time: 17:49
 */

namespace rollun\datastore\DataStore\Traits;


use rollun\datastore\DataStore\DataStoreException;

trait NoSupportIteratorTrait
{
    /**
     * @inheritdoc
     */
    public function getIterator()
    {
        throw new DataStoreException("Method don't support.");
    }
}