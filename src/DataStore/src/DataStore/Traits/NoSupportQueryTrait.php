<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Traits;

use Graviton\RqlParser\Query;
use rollun\datastore\DataStore\DataStoreException;

/**
 * Use this trait to disable 'query' method in datastore
 *
 * Trait NoSupportQueryTrait
 * @package rollun\datastore\DataStore\Traits
 */
trait NoSupportQueryTrait
{
    /**
     * @param Query $query
     * @throws DataStoreException
     */
    public function query(Query $query)
    {
        trigger_error(NoSupportQueryTrait::class . ' trait is deprecated', E_USER_DEPRECATED);

        throw new DataStoreException("Method don't support.");
    }
}
