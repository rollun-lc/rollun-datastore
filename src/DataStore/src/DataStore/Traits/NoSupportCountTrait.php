<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Traits;

use rollun\datastore\DataStore\DataStoreException;

/**
 * Use this trait to disable 'count' method in datastore
 *
 * Trait NoSupportCountTrait
 * @package rollun\datastore\DataStore\Traits
 */
trait NoSupportCountTrait
{
    /**
     * @throws DataStoreException
     */
    public function count()
    {
        trigger_error(NoSupportCountTrait::class . ' trait is deprecated', E_USER_DEPRECATED);

        throw new DataStoreException("Method don't support.");
    }
}
