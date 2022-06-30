<?php

declare(strict_types=1);

namespace rollun\datastore\DataStore\Formatter;

use DateTimeInterface;

class DateTimeOrNullFormatter implements FormatterInterface
{
    private const DEFAULT_FORMAT = 'Y-m-d H:i:s.u';

    /**
     * @var string
     */
    private $format;

    public function __construct(?string $format = null)
    {
        $this->format = $format ?? self::DEFAULT_FORMAT;
    }

    public function format($value)
    {
        if ($value === null) {
            return null;
        }
        if ($value instanceof DateTimeInterface) {
            return $value->format($this->format);
        }
        throw new \RuntimeException('Value should be date time object or null.');
    }
}
