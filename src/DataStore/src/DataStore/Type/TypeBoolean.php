<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Type;

class TypeBoolean extends TypeAbstract
{
    /**
     * @return string
     */
    public static function getTypeName()
    {
        return 'boolean';
    }

    /**
     * @return bool
     * @throws TypeException
     */
    public function toTypeValue()
    {
        try {
            return boolval($this->value);
        } catch (\Exception $e) {
            throw new TypeException($e->getMessage());
        }
    }
}
