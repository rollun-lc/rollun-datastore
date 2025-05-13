<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Formatter;

class BooleanFormatter extends AbstractFormatter
{
    /**
     * @param $value
     * @return boolean
     */
    public function format($value)
    {
        return $this->getTypeCaster('bool', $value)->toTypeValue();
    }
}
