<?php

namespace rollun\repository\Casting;

use rollun\repository\Interfaces\ModelCastingInterface;

class DateCasting implements ModelCastingInterface
{
    public function get($value)
    {
        if (is_string($value)) {
            $value = new \DateTime($value);
        }

        return $value;
    }

    public function set($value)
    {
        if ($value instanceof \DateTimeInterface) {
            $value = $value->format('Y-m-d H:i:s.v');
        }

        return $value;
    }
}
