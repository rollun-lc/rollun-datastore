<?php

namespace rollun\datastore\DataStore\Query;

use Graviton\RqlParser\Query;

interface QueryAdapter
{
    public function adapt(Query $query): Query;
}
