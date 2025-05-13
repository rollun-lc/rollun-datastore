<?php

namespace rollun\repository\Casting;

use rollun\repository\Interfaces\ModelCastingInterface;

class SerializeCasting implements ModelCastingInterface
{
    /**
     * @param $value
     *
     * @return mixed
     *
     * @todo specify classes allowed
     */
    public function get($value)
    {
        return unserialize($value);
    }

    /**
     * @param $value
     *
     * @return string
     */
    public function set($value)
    {
        return serialize($value);
    }
}
