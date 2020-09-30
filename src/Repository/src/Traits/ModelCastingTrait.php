<?php


namespace rollun\repository\Traits;


trait ModelCastingTrait
{
    protected function cast($name, $value)
    {
        $type = $this->casting[$name];
        $method = 'cast' . str_replace('_', '', ucwords($type, '_'));
        if (method_exists($this, $method)) {
            $value = $this->{$method}($value);
        }

        return $value;
    }

    protected function castInt($value)
    {
        return (int) $value;
    }

    protected function castFloat($value)
    {
        return (float) $value;
    }

    /**
     * @todo
     *
     * @param $value
     *
     * @return mixed|string
     */
    protected function castArray($value)
    {
        if (is_string($value)) {
            if (is_array(json_decode($value, true))) {
                return json_decode($value, true);
            }

            if (is_array($result = @unserialize($value))) {
                return unserialize($value);
            }
        }

        return $value;
    }
}