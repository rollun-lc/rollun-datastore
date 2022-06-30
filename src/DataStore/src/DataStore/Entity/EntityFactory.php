<?php

declare(strict_types=1);

namespace rollun\datastore\DataStore\Entity;

interface EntityFactory
{
    /**
     * Create entity from typed record.
     * Typed record is associative array [fieldName => fieldValue] where field name and value are same as in the scheme.
     * @return object|array
     */
    public function fromRecord(array $record);
}
