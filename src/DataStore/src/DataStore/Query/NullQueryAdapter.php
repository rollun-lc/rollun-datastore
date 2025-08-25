<?php

declare(strict_types=1);

namespace rollun\datastore\DataStore\Query;

use Graviton\RqlParser\Query;

class NullQueryAdapter implements QueryAdapter
{
    public function adapt(Query $query): Query
    {
        return $query;
    }
}
