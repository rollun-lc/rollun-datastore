<?php

declare(strict_types=1);

namespace rollun\test\unit\DataStore\DataStore\Aspect\AspectSchema;

class StringValueObject
{
    /**
     * @var string
     */
    private $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
