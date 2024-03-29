<?php

declare(strict_types=1);

namespace rollun\test\unit\DataStore\DataStore\Aspect\AspectSchema;

class IntValueObject
{
    /**
     * @var int
     */
    private $value;

    public function __construct(int $value)
    {
        $this->value = $value;
    }

    public function getValue(): int
    {
        return $this->value;
    }
}
