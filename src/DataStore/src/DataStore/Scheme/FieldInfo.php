<?php

declare(strict_types=1);

namespace rollun\datastore\DataStore\Scheme;

use rollun\datastore\DataStore\Formatter\FormatterInterface;

class FieldInfo
{
    public function __construct(private TypeFactory $typeFactory, private FormatterInterface $formatter, private Getter $getter, private bool $nullable)
    {
    }

    public function getTypeFactory(): TypeFactory
    {
        return $this->typeFactory;
    }

    public function getFormatter(): FormatterInterface
    {
        return $this->formatter;
    }

    public function getGetter(): Getter
    {
        return $this->getter;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }
}
