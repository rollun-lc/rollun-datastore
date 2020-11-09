<?php


namespace rollun\repository\Casting;


use rollun\repository\Interfaces\ModelCastingInterface;

class ObjectCasting implements ModelCastingInterface
{
    /**
     * @param $value
     *
     * @return mixed
     */
    public function get($value)
    {
        return json_decode($value, false, 512, JSON_FORCE_OBJECT);
    }

    /**
     * @param $value
     *
     * @return false|string
     */
    public function set($value)
    {
        if (!is_object($value) && !is_array($value)) {
            $value = (object) $value;
        }
        return json_encode($value, JSON_FORCE_OBJECT|JSON_NUMERIC_CHECK);
    }
}