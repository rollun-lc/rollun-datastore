<?php

declare(strict_types=1);

namespace rollun\datastore\DataStore\Formatter;

class JsonOrNullFormatter implements FormatterInterface
{
    public function format($value)
    {
        return $value === null ? null : json_encode($value);
    }
}
