<?php


namespace rollun\repository\Casting;


use rollun\repository\Interfaces\ModelCastingInterface;

class ArrayCasting implements ModelCastingInterface
{
    /**
     * @param $value
     *
     * @return mixed
     */
    public function get($value)
    {
        if (empty($value)) {
            return [];
        }

        return json_decode($value, true);
    }

    /**
     * @param $value
     *
     * @return false|string
     */
    public function set($value)
    {
        if (empty($value)) {
            return $value;
        }

        // TODO
        if (is_string($value) && $this->isJson($value)) {
            //return $value;
            $value = $this->get($value);
        }

        if (!is_array($value)) {
            $value = (array) $value;
        }

        return json_encode($value, JSON_NUMERIC_CHECK);
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