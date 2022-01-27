<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Interfaces;

use Laminas\Db\TableGateway\TableGateway;

/**
 * Interface DbTableInterface
 * @package rollun\datastore\DataStore\Interfaces
 */
interface DbTableInterface extends DataStoresInterface
{
    /**
     * @return TableGateway
     */
    public function getDbTable();
}
