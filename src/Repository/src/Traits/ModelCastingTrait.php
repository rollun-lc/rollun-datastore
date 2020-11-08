<?php


namespace rollun\repository\Traits;


use rollun\repository\Interfaces\ModelCastingInterface;

trait ModelCastingTrait
{
    protected $castObjects = [];

    protected function cast($name, $value, $direction = ModelCastingInterface::DIRECTION_GET)
    {
        $type = $this->casting[$name];

        if (in_array($type, [
            ModelCastingInterface::CAST_INT,
            ModelCastingInterface::CAST_INTEGER,
            ModelCastingInterface::CAST_FLOAT,
            ModelCastingInterface::CAST_DOUBLE,
            ModelCastingInterface::CAST_STRING,
        ])) {
            $method = 'cast' . str_replace('_', '', ucwords($type, '_'));
            if (method_exists($this, $method)) {
                return $this->{$method}($value);
            }
        }

        if (in_array($type, [
            ModelCastingInterface::CAST_JSON,
            ModelCastingInterface::CAST_ARRAY,
            ModelCastingInterface::CAST_OBJECT,
            ModelCastingInterface::CAST_SERIALIZE,
        ])) {
            $type = 'rollun\\repository\\Casting\\' . ucfirst($type) . 'Casting';
        }

        if (!class_exists($type) || !is_a($type, ModelCastingInterface::class, true)) {
            throw new \Exception('Casting class must exist and implement ' . ModelCastingInterface::class);
        }

        $castingObject = $this->getCastingObject($type);
        return $castingObject->{$direction}($value);
    }

    protected function getCastingObject($type)
    {
        if (!array_key_exists($type, $this->castObjects)) {
            $this->castObjects[$type] = new $type();
        }

        return $this->castObjects[$type];
    }

    protected function needCast($name)
    {
        return (
            isset($this->casting)
            && is_array($this->casting)
            && array_key_exists($name, $this->casting)
        );
    }

    protected function castInt($value)
    {
        return (int) $value;
    }

    protected function castInteger($value)
    {
        return $this->castInt($value);
    }

    protected function castFloat($value)
    {
        return (float) $value;
    }

    protected function castDouble($value)
    {
        return $this->castFloat($value);
    }

    /**
     * @param $value
     * @return string
     */
    protected function castString($value)
    {
        return (string) $value;
    }

    /*protected function castArray($value)
    {
        return (array) $value;
    }

    protected function castObject($value)
    {
        return (object) $value;
    }*/

    /**
     * @todo
     *
     * @param $value
     *
     * @return mixed|string
     */
    /*protected function castArray($value)
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
    }*/
}