<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Formatter;

/**
 * Formatter for transform incoming value for datastore for its correct read
 *
 * Interface FormatterInterface
 * @package rollun\datastore\DataStore\Formatter
 */
interface FormatterInterface
{
    /**
     * Format value to a particular view
     *
     * @param $value
     * @return mixed
     */
    public function format($value);
}
