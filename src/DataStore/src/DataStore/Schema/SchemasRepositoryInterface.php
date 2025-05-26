<?php

namespace rollun\datastore\DataStore\Schema;

interface SchemasRepositoryInterface
{
    /**
     * @return null|array {
     *      $schema?: string,
     *      $id?: string,
     *      title?: string,
     *      description?: string,
     *      type: string,
     *      properties: array<string, array{
     *          description?: string,
     *          type?: string
     *      }>,
     *      ...array<string, mixed>
     *  } JSON schema
     */
    public function findSchema(string $dataStoreName): ?array;
}
