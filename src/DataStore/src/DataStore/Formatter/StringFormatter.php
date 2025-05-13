<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Formatter;

class StringFormatter extends AbstractFormatter
{
    /**
     * @param $value
     * @return string
     */
    public function format($value)
    {
        return $this->getTypeCaster('string', $value)->toTypeValue();
    }
}
