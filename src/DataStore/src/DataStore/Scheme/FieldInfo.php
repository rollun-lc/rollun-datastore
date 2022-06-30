<?php

declare(strict_types=1);

namespace rollun\datastore\DataStore\Scheme;

use rollun\datastore\DataStore\Formatter\FormatterInterface;

class FieldInfo
{
    /**
     * @var TypeFactory
     */
    private $typeFactory;

    /**
     * @var FormatterInterface
     */
    private $formatter;

    /**
     * @var Getter
     */
    private $getter;

    /**
     * @var bool
     */
    private $nullable;

    public function __construct(
        TypeFactory $typeFactory,
        FormatterInterface $formatter,
        Getter $getter,
        bool $nullable
    ) {
        $this->typeFactory = $typeFactory;
        $this->formatter = $formatter;
        $this->getter = $getter;
        $this->nullable = $nullable;
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
