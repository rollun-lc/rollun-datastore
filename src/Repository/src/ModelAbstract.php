<?php

namespace rollun\repository;


use rollun\repository\Interfaces\ModelInterface;
use rollun\repository\ModelRepository;

abstract class ModelAbstract implements ModelInterface
{
    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * Model constructor.
     * @param array $attributes
     */
    public function __construct($attributes = [])
    {
        $this->fill($attributes);
    }

    public function __set($name, $value)
    {
        $this->setAttribute($name, $value);
    }

    public function __get($name)
    {
        return $this->getAttribute($name);
    }

    public function __isset($name)
    {
        return array_key_exists($name, $this->attributes);
    }

    public function __clone()
    {
        $this->attributes = [];
    }

    public function clone()
    {
        $attributes = $this->attributes;
        $model = clone $this;
        $model->fill($attributes);
    }

    public function getAttribute($name)
    {
        if (isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }

        return null;
    }

    public function setAttribute($name, $value)
    {
        $this->attributes[$name] = $value;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function fill($attributes)
    {
        foreach ($attributes as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * @todo Make public and private attributes
     *
     * @return array
     */
    public function toArray()
    {
        $attributes = $this->getAttributes();
        foreach ($attributes as $key => $attribute) {
            if (in_array($key, $this->hidden())) {
                unset($attributes[$key]);
            }
        }
        return $attributes;
    }

    protected function hidden()
    {
        return [];
    }
}