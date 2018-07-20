<?php

namespace rollun\datastore\DataStore\Traits;

use ReflectionClass;
use ReflectionException;
use rollun\datastore\DataStore\DataStoreException;

trait FieldsTrait
{
    /**
     * @var array
     */
    private static $filedCacheArray = null;

    /**
     *
     */
    private static function getFields()
    {
        if (!self::$filedCacheArray) {
            self::$filedCacheArray = [];
        }
        $calledClass = get_called_class();
        if (!array_key_exists($calledClass, self::$filedCacheArray)) {
            try {
                $reflect = new ReflectionClass($calledClass);
            } catch (ReflectionException $exception) {
                throw new DataStoreException("Can't create reflection", $exception->getCode(), $exception);
            }
            $constant = $reflect->getConstants();
            $fields = array_filter($constant, function ($key) {
                return preg_match('/FIELD_([\w_]+)/', $key);
            }, ARRAY_FILTER_USE_KEY);
            self::$filedCacheArray[$calledClass] = $fields;
        }
        return self::$filedCacheArray[$calledClass];
    }

    /**
     * @param $name
     * @return bool
     */
    public static function isFieldExist($name)
    {
        $fields = array_values(self::getFields());
        return in_array($name, $fields, true);
    }
}