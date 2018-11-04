<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore;

use InvalidArgumentException;
use ReflectionClass;
use rollun\datastore\DataStore\Type\TypeInterface;

class BaseDto
{
    /**
     * @param array $data
     * @return BaseDto
     * @throws \ReflectionException
     */
    public static function createFromArray(array $data)
    {
        $reflection = new ReflectionClass(static::class);
        $reflectionParameters = $reflection->getConstructor()->getParameters();
        $arguments = [];

        foreach ($reflectionParameters as $reflectionParameter) {
            if (!isset($data[$reflectionParameter->getName()])) {
                throw new InvalidArgumentException("Missing '{$reflectionParameter->getName()}' parameter");
            }

            if (!is_a($data[$reflectionParameter->getName()], TypeInterface::class, true)) {
                throw new InvalidArgumentException(
                    "Invalid type for '{$reflectionParameter->getName()}' parameter"
                );
            }

            $arguments[] = $data[$reflectionParameter->getName()];
        }

        return new static(...$arguments);
    }
}
