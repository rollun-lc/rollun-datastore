<?php

declare(strict_types=1);

namespace rollun\datastore\DataStore\Scheme;

interface Getter
{
    /**
     * @param object|array $object
     * @return mixed
     */
    public function get($object);
}
