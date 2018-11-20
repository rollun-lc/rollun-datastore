<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Type;

class TypeInt extends TypeAbstract
{
    /**
     * @return string
     */
    public static function getTypeName()
    {
        return 'integer';
    }

    /**
     * @return int
     * @throws TypeException
     */
    public function toTypeValue()
    {
        try {
            return intval($this->value);
        } catch (\Exception $e) {
            throw new TypeException($e->getMessage());
        }
    }
}
