<?php


namespace rollun\repository\Interfaces;


/**
 * Interface FieldMapperInterface
 *
 * @package rollun\repository\Interfaces
 */
interface FieldMapperInterface
{
    /**
     * @param array $data
     *
     * @return array
     */
    public function map(array $data): array;
}