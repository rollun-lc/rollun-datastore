<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 13.04.18
 * Time: 6:50 PM
 */

namespace rollun\datastore\DataStore\Traits;
use rollun\datastore\DataStore\DataStoreException;


/**
 * Trait GetFieldsInfoFromConstTrait
 * Realisation getFieldsInfo method to return list of supported fields name, from
 * FIELD_ const.
 * !!! IMPORTANT Returned fields without typing.
 * @package rollun\datastore\DataStore\Traits
 */trait GetFieldsInfoFromConstTrait
{

    /**
     * @return array
     * @throws DataStoreException
     */
    public function getFieldsInfo() {
        try {
            $reflection = new \ReflectionClass($this);
            $constants = $reflection->getConstants();
            $fieldsConstat = array_filter($constants, function ($key) {
                return preg_match('/^FIELD_([A-Z_]+)/', $key);
            }, ARRAY_FILTER_USE_KEY);
            $fieldsInfo = array_combine(array_values($fieldsConstat), array_fill(0, count($fieldsConstat), []));
        } catch (\ReflectionException $exception) {
            throw new DataStoreException("Exception by get fields info.", $exception->getCode(), $exception);
        }
        return $fieldsInfo;
    }
}
