<?php


namespace rollun\repository\Casting;


use rollun\repository\Interfaces\ModelCastingInterface;

class ObjectCasting implements ModelCastingInterface
{
    /**
     * @param $value
     *
     * @return mixed
     */
    public function get($value)
    {
        if (empty($value)) {
            return null;
        }

        return json_decode($value, false, 512, JSON_FORCE_OBJECT);
    }

    /**
     * @param $value
     *
     * @return false|string
     */
    public function set($value)
    {
        if (empty($value) && is_scalar($value)) {
            return $value;
        }

        // TODO
        if (is_string($value) && $this->isJson($value)) {
            //return $value;
            $value = $this->get($value);
        }

        if (!is_object($value)) {
            $value = (object) $value;
        }

        return json_encode($value, JSON_FORCE_OBJECT|JSON_NUMERIC_CHECK);
    }

    /**
     * @param $string
     * @return bool
     *
     * @todo
     */
    protected function isJson($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}