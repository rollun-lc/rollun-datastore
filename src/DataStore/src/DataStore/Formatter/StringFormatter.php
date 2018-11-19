<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Formatter;

use rollun\datastore\DataStore\Type\TypeString;

class StringFormatter implements FormatterInterface
{
    public function format($value)
    {
        return (new TypeString($value))->toTypeValue();
    }
}
