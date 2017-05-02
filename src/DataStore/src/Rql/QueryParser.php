<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 14.01.17
 * Time: 11:01 AM
 */

namespace rollun\datastore\Rql;


use Xiag\Rql\Parser\Parser;

class QueryParser extends Parser
{
    protected function createQueryBuilder()
    {
        return new RqlQueryBuilder();
    }

}