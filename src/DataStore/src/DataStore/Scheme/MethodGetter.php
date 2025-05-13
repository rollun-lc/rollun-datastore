<?php

declare(strict_types=1);

namespace rollun\datastore\DataStore\Scheme;

class MethodGetter implements Getter
{
    public function __construct(private string $methodName) {}

    public function get($object)
    {
        return $object->{$this->methodName}();
    }
}
