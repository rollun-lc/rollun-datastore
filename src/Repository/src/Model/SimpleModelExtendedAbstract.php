<?php

namespace rollun\repository\Model;


use rollun\datastore\DataStore\Memory;
use rollun\dic\InsideConstruct;
use rollun\repository\ModelAbstract;

class SimpleModelExtendedAbstract extends ModelAbstract
{
    protected $memory;

    public function __construct($attributes = [], Memory $memory = null)
    {
        parent::__construct($attributes);

        InsideConstruct::setConstructParams(['memory' => Memory::class]);
    }

    public function hidden(): array
    {
        return ['hidden'];
    }
}