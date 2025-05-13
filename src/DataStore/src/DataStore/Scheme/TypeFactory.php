<?php

declare(strict_types=1);

namespace rollun\datastore\DataStore\Scheme;

use rollun\datastore\DataStore\Type\TypeInterface;

interface TypeFactory
{
    public function create($value): TypeInterface;
}
