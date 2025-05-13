<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Type;

abstract class TypeAbstract implements TypeInterface
{
    /**
     * TypeAbstract constructor.
     * @param mixed $value
     */
    public function __construct(protected $value) {}
}
