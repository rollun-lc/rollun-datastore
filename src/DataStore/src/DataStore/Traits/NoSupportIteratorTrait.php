<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Traits;

use rollun\datastore\DataStore\DataStoreException;

/**
 * Use this trait to disable 'getIterator' method in datastore
 *
 * Trait NoSupportIteratorTrait
 * @package rollun\datastore\DataStore\Traits
 */
trait NoSupportIteratorTrait
{
    /**
     * @throws DataStoreException
     */
    public function getIterator()
    {
        trigger_error(NoSupportIteratorTrait::class . ' trait is deprecated', E_USER_DEPRECATED);

        throw new DataStoreException("Method don't support.");
    }
}
