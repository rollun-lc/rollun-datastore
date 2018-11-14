<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Type;

class TypeInt implements TypeInterface
{
    protected $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public static function getTypeName()
    {
        return 'integer';
    }

    public function toTypeValue()
    {
        try {
            return intval($this->value);
        } catch (\Exception $e) {
            throw new TypeException($e->getMessage());
        }
    }
}