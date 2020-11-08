<?php


namespace rollun\repository\Casting;


use rollun\repository\Interfaces\ModelCastingInterface;

class ArrayCasting implements ModelCastingInterface
{

    public function get($value)
    {
        return json_decode($value, true);
    }

    public function set($value)
    {
        return json_encode($value, JSON_OBJECT_AS_ARRAY);
    }
}