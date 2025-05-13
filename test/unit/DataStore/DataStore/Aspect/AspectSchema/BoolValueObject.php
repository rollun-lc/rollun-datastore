<?php

declare(strict_types=1);

namespace rollun\test\unit\DataStore\DataStore\Aspect\AspectSchema;

class BoolValueObject
{
    public function __construct(private bool $bool)
    {
    }

    public function getValue(): bool
    {
        return $this->bool;
    }
}
