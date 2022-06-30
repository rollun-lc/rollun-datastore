<?php

declare(strict_types=1);

namespace rollun\datastore\DataStore\Scheme;

class MethodGetter implements Getter
{
    /**
     * @var string
     */
    private $methodName;

    public function __construct(string $methodName)
    {
        $this->methodName = $methodName;
    }

    public function get($object)
    {
        return $object->{$this->methodName}();
    }
}
