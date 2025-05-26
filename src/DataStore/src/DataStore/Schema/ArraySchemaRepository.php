<?php

namespace rollun\datastore\DataStore\Schema;

class ArraySchemaRepository implements SchemasRepositoryInterface
{
    /**
     * @param array<string,array> $schemas
     */
    public function __construct(
        private array $schemas
    ) {}

    public function findSchema(string $dataStoreName): ?array
    {
        return $this->schemas[$dataStoreName] ?? null;
    }
}
