<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore;

use InvalidArgumentException;
use rollun\datastore\DataStore\Type\TypeInterface;

class BaseDto
{
    /**
     * BaseDto constructor.
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        foreach ($data as $field => $typedValue) {
            if (!($typedValue instanceof TypeInterface)) {
                throw new InvalidArgumentException(
                    'Expected instance of ' . TypeInterface::class . ' for field ' . $field
                );
            }

            if (!property_exists($this, $field)) {
                throw new InvalidArgumentException("Unknown property '$field' in " . static::class);
            }

            $this->{$field} = $typedValue;
        }
    }
}
