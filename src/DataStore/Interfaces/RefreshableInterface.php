<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 04.07.16
 * Time: 11:42
 */

namespace rollun\datastore\DataStore\Interfaces;

use rollun\datastore\DataStore\DataStoreException;

interface RefreshableInterface
{
    /**
     * @return null
     * @throws DataStoreException
     */
    public function refresh();
}
