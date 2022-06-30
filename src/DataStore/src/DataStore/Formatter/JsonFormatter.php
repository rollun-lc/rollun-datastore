<?php

declare(strict_types=1);

namespace rollun\datastore\DataStore\Formatter;

class JsonFormatter implements FormatterInterface
{
    public function format($value)
    {
        return json_encode($value);
    }
}