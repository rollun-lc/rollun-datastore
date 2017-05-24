<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 24.05.17
 * Time: 19:59
 */

namespace rollun\datastore\DataStore\Interfaces;

use Zend\Db\TableGateway\TableGateway;

interface DbTableInterface extends DataStoresInterface
{
    /**
     * @return TableGateway
     */
    public function getDbTable();
}
