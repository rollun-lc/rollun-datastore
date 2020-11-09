<?php


namespace rollun\repository\Casting;


use rollun\repository\Interfaces\ModelCastingInterface;

class ArrayCasting implements ModelCastingInterface
{
    /**
     * @param $value
     *
     * @return mixed
     */
    public function get($value)
    {
        return json_decode($value, true);
    }

    /**
     * @param $value
     *
     * @return false|string
     */
    public function set($value)
    {
        if (!is_array($value) && !is_object($value)) {
            $value = (array) $value;
        }
        return json_encode($value, JSON_NUMERIC_CHECK);
    }
}