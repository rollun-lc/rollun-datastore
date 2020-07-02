<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Interfaces;

/**
 * Interface DateTimeInterface
 * @package rollun\datastore\DataStore\Interfaces
 */
interface DateTimeInterface extends DataStoresInterface
{
    public const FIELD_DATETIME = "datetime";

    public const FIELD_CREATED_AT = 'created_at';

    public const FIELD_UPDATED_AT = 'updated_at';
}
