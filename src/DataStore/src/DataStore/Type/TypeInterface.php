<?php

namespace rollun\datastore\DataStore\Type;

interface TypeInterface
{
    /**
     * Get converted value to defined type
     *
     * @return mixed
     */
    public function toTypeValue();

    /**
     * Return type name
     *
     * @return mixed
     */
    public static function getTypeName();
}
