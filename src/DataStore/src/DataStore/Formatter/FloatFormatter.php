<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Formatter;

class FloatFormatter extends AbstractFormatter
{
    /**
     * @param $value
     * @return float
     */
    public function format($value)
    {
        return $this->getTypeCaster('float', $value)->toTypeValue();
    }
}
