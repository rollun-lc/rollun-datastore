<?php

namespace rollun\repository\Casting;

use rollun\repository\Interfaces\ModelCastingInterface;

/**
 * Class JsonCasting
 *
 * @package rollun\repository\Casting
 *
 * @todo
 */
class JsonCasting implements ModelCastingInterface
{
    public function get($value)
    {
        return json_decode($value);
    }

    public function set($value)
    {
        return json_encode($value, JSON_NUMERIC_CHECK);
    }
}
