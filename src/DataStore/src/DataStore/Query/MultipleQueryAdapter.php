<?php

namespace rollun\datastore\DataStore\Query;

use Xiag\Rql\Parser\Query;

class MultipleQueryAdapter implements QueryAdapter
{
    /**
     * @var QueryAdapter[]
     */
    private $adapters;

    /**
     * MultipleQueryAdapter constructor.
     * @param QueryAdapter[] $adapters
     */
    public function __construct(array $adapters)
    {
        foreach ($adapters as $adapter) {
            if (!($adapter instanceof QueryAdapter)) {
                throw new \InvalidArgumentException('Adapters must be instance of ' . QueryAdapter::class);
            }
        }
        $this->adapters = $adapters;
    }

    public function adapt(Query $query): Query
    {
        foreach ($this->adapters as $adapter) {
            $query = $adapter->adapt($query);
        }

        return $query;
    }
}
