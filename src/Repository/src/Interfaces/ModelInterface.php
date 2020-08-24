<?php

namespace rollun\repository\Interfaces;


/**
 * Interface ModelInterface
 *
 * @package rollun\repository\Interfaces
 */
interface ModelInterface
{
    /**
     * @return array
     */
    public function toArray(): array;

    /**
     * @return bool
     */
    public function isChanged(): bool;

    /**
     * @return array
     */
    public function getChanges(): array;

    /**
     * @return bool
     */
    public function isExists(): bool;

    /**
     * @param bool $exists
     */
    public function setExists(bool $exists): void;
}