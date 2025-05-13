<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Traits;

use rollun\datastore\DataStore\DataStoreException;

/**
 * Use this trait to disable 'getIdentifier' method in datastore
 *
 * Trait NoSupportGetIdentifier
 * @package rollun\datastore\DataStore\Traits
 */
trait NoSupportGetIdentifier
{
    /**
     * @throws DataStoreException
     */
    public function getIdentifier()
    {
        trigger_error(NoSupportGetIdentifier::class . ' trait is deprecated', E_USER_DEPRECATED);

        throw new DataStoreException("Method don't support.");
    }
}
