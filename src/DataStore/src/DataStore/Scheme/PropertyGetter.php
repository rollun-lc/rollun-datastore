<?php

declare(strict_types=1);

namespace rollun\datastore\DataStore\Scheme;

class PropertyGetter implements Getter
{
    public function __construct(private string $propertyName) {}

    /**
     * @param object|array $object
     * @return mixed
     */
    public function get($object)
    {
        return $object->{$this->propertyName};
    }
}
