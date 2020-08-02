<?php

namespace rollun\repository\Interfaces;


interface ModelInterface
{
    public function toArray(): array;

    public function isChanged(): bool;

    public function getChanged(): array;

    public function isExists(): bool;

    public function setExists(bool $exists): void;
}