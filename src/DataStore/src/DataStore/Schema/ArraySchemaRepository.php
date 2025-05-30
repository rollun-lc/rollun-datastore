<?php

namespace rollun\datastore\DataStore\Schema;

class ArraySchemaRepository implements SchemasRepositoryInterface
{
    /**
     * @var array<string,array> $schemas
     */
    private $schemas;

    /**
     * @param array<string,array> $schemas
     */
    public function __construct(array $schemas) {
        $this->schemas = $schemas;
    }

    public function findSchema(string $dataStoreName): ?array
    {
        return $this->schemas[$dataStoreName] ?? null;
    }
}
