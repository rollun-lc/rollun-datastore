<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Aspect;

use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\Traits\NoSupportCreateTrait;
use rollun\datastore\DataStore\Traits\NoSupportDeleteAllTrait;
use rollun\datastore\DataStore\Traits\NoSupportDeleteTrait;
use rollun\datastore\DataStore\Traits\NoSupportUpdateTrait;
use Xiag\Rql\Parser\Query;

/**
 * Class AspectReadOnly
 *
 * This is wrapper for any type of datastore which disallow all methods except ReadInterface.
 * It is useful for wrapping datastores from production in local environment.
 *
 * @see AspectAbstractFactory
 * @package rollun\datastore\DataStore\Aspect
 */
class AspectReadOnly extends AspectAbstract
{
    use NoSupportCreateTrait;
    use NoSupportDeleteAllTrait;
    use NoSupportDeleteTrait;
    use NoSupportUpdateTrait;

    public function multiCreate($records)
    {
        throw new DataStoreException("Method don't support.");
    }

    public function multiUpdate($records)
    {
        throw new DataStoreException("Method don't support.");
    }

    public function queriedUpdate($record, Query $query)
    {
        throw new DataStoreException("Method don't support.");
    }

    public function rewrite($record)
    {
        throw new DataStoreException("Method don't support.");
    }

    public function queriedDelete(Query $query)
    {
        throw new DataStoreException("Method don't support.");
    }
}
