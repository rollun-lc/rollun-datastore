<?php

declare(strict_types=1);

namespace rollun\datastore\DataStore\Formatter;

class NullFormatter implements FormatterInterface
{
    public function format($value)
    {
        return $value;
    }
}
