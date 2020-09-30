<?php

namespace rollun\repository\Interfaces;


use rollun\utils\Interfaces\ArrayableInterface;

/**
 * Interface ModelInterface
 *
 * @package rollun\repository\Interfaces
 */
interface ModelInterface extends ArrayableInterface
{
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