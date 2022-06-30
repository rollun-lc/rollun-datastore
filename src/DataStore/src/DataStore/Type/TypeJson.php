<?php

declare(strict_types=1);

namespace rollun\datastore\DataStore\Type;

class TypeJson extends TypeAbstract
{
    public static function getTypeName(): string
    {
        return 'json';
    }

    public function toTypeValue()
    {
        $data = json_decode($this->value, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new TypeException('Unable to decode json: ' . json_last_error_msg());
        }
        return $data;
    }
}
