<?php

declare(strict_types=1);

namespace rollun\datastore\DataStore\Type;

use DateTimeImmutable;
use Exception;

class TypeDateTimeImmutable extends TypeAbstract
{
    public static function getTypeName(): string
    {
        return 'date-time-immutable';
    }

    public function toTypeValue(): DateTimeImmutable
    {
        try {
            return new DateTimeImmutable($this->value);
        } catch (Exception $e) {
            throw new TypeException($e->getMessage());
        }
    }
}
