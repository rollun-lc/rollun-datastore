<?php


namespace rollun\repository\Model;


use rollun\repository\Interfaces\ModelInterface;

class SimpleModelImplementedInterface implements ModelInterface
{
    public $id;

    public $field;

    public function toArray()
    {
        return [
            'id' => $this->id,
            'field' => $this->field,
        ];
    }
}