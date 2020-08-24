<?php


namespace rollun\repository\Model;


use rollun\repository\Interfaces\ModelInterface;

class SimpleModelImplementedInterface implements ModelInterface
{
    public $id;

    public $field;

    public $changed = false;

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'field' => $this->field,
        ];
    }

    public function isChanged(): bool
    {
        return $this->changed;
    }

    public function getChanges(): array
    {
        // TODO: Implement getChanged() method.
    }

    public function isExists(): bool
    {
        // TODO: Implement isExists() method.
    }

    public function setExists(bool $exists): void
    {
        // TODO: Implement setExists() method.
    }
}