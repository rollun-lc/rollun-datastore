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
}
