<?php

declare(strict_types=1);

namespace rollun\test\unit\DataStore\DataStore\Aspect\AspectSchema;

class FloatValueObject
{
    /**
     * @var float
     */
    private $value;

    public function __construct(float $value)
    {
        $this->value = $value;
    }

    public function getValue(): float
    {
        return $this->value;
    }
}
