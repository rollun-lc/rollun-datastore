<?php


namespace rollun\repository\Interfaces;


interface FieldMapperInterface
{
    public function map(array $data): array;
}