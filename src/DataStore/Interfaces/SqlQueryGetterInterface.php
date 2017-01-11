<?php

/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 26.10.16
 * Time: 5:50 PM
 */

namespace rollun\datastore\DataStore\Interfaces;

use Xiag\Rql\Parser\Query;

interface SqlQueryGetterInterface
{

    public function getSqlQuery(Query $query);
}
