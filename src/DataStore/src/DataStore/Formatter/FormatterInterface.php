<?php

namespace rollun\datastore\DataStore\Formatter;

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
