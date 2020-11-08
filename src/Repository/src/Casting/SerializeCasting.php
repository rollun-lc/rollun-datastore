<?php


namespace rollun\repository\Casting;


use rollun\repository\Interfaces\ModelCastingInterface;

class SerializeCasting implements ModelCastingInterface
{

    public function get($value)
    {
        return unserialize($value);
    }

    public function set($value)
    {
        return serialize($value);
    }
}