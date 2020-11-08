<?php


namespace rollun\repository\Casting;


use rollun\repository\Interfaces\ModelCastingInterface;

class ObjectCasting implements ModelCastingInterface
{

    public function get($value)
    {
        return json_decode($value, false, 512, JSON_FORCE_OBJECT);
    }

    public function set($value)
    {
        return json_encode($value, JSON_FORCE_OBJECT);
    }
}