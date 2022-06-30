<?php

declare(strict_types=1);

namespace rollun\test\unit\DataStore\DataStore\Aspect\AspectSchema;

class BoolValueObject
{
    /**
     * @var bool
     */
    private $bool;

    public function __construct(bool $bool)
    {
        $this->bool = $bool;
    }

    public function getValue(): bool
    {
        return $this->bool;
    }
}
