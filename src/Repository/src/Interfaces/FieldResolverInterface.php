<?php


namespace rollun\repository\Interfaces;


use Zend\Hydrator\HydratorInterface;

interface FieldResolverInterface
{
    public function resolve(array $data): array;
}