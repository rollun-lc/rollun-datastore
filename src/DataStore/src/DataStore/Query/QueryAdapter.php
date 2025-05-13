<?php

namespace rollun\datastore\DataStore\Query;

use Xiag\Rql\Parser\Query;

interface QueryAdapter
{
    public function adapt(Query $query): Query;
}
