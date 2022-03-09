<?php

declare(strict_types=1);

namespace rollun\datastore\DataStore\Scheme;

class PropertyGetter implements Getter
{
    /**
     * @var string
     */
    private $propertyName;

    public function __construct(string $propertyName)
    {
        $this->propertyName = $propertyName;
    }

    /**
     * @param object|array $object
     * @return mixed
     */
    public function get($object)
    {
        return $object->{$this->propertyName};
    }
}
