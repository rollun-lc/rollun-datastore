<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Formatter;

class IntFormatter extends AbstractFormatter
{
    /**
     * @param $value
     * @return integer
     */
    public function format($value)
    {
        return $this->getTypeCaster('int', $value)->toTypeValue();
    }
}
