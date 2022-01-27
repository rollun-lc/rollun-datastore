<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Type;

class TypeChar extends TypeAbstract
{
    /**
     * @return string
     */
    public static function getTypeName()
    {
        return 'char';
    }

    /**
     * @return string
     * @throws TypeException
     */
    public function toTypeValue()
    {
        if (is_resource($this->value)) {
            throw new TypeException('Resource could not be converted to string');
        }

        try {
            $value = (string) $this->value;

            if (mb_strlen($value) != strlen($value)) {
                throw new TypeException('Multibyte symbols could not be converted to char');
            }

            $value = substr($value, 0, 1);

            if ($value === false) {
                $value = '';
            }

            if (!strlen($value)) {
                $value = chr(0);
            } else {
                $value = chr(ord($value));
            }

            return $value;
        } catch (\Throwable $e) {
            throw new TypeException($e->getMessage());
        }
    }
}
