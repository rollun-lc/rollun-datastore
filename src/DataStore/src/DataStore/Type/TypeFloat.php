<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Type;

class TypeFloat extends TypeAbstract
{
    /**
     * @return string
     */
    public static function getTypeName()
    {
        return 'float';
    }

    /**
     * @return float
     * @throws TypeException
     */
    public function toTypeValue()
    {
        try {
            return floatval($this->value);
        } catch (\Exception $e) {
            throw new TypeException($e->getMessage());
        }
    }
}
