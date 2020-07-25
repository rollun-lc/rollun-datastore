<?php


namespace rollun\repository\Interfaces;


interface FieldResolverInterface
{
    public function resolve(array $data): array;
}