<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Interfaces;

/**
 * Interface SchemableInterface
 * @package rollun\datastore\DataStore\Interfaces
 */
interface SchemableInterface
{
    /**
     * Return scheme for datastore
     *
     * @return mixed
     */
    public function getScheme();
}
