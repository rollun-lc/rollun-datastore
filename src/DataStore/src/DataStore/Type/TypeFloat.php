<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Type;

class TypeFloat implements TypeInterface
{
    protected $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public static function getTypeName()
    {
        return 'float';
    }

    public function toTypeValue()
    {
        try {
            return floatval($this->value);
        } catch (\Exception $e) {
            throw new TypeException($e->getMessage());
        }
    }
}
