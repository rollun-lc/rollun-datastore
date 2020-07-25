<?php

namespace rollun\repository;


use ArrayAccess;
use rollun\repository\Interfaces\ModelHiddenFieldInterface;
use rollun\repository\Interfaces\ModelInterface;
use rollun\repository\Traits\ModelArrayAccess;

abstract class ModelAbstract implements ModelInterface, ModelHiddenFieldInterface, ArrayAccess
{
    use ModelArrayAccess;

    /**
     * @var array
     */
    protected $attributes = [];

    protected $original = [];

    /**
     * Model constructor.
     * @param array $attributes
     */
    public function __construct($attributes = [])
    {
        $this->fill($attributes);

        $this->original = $this->getAttributes();
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
        return $this->hasAttribute($name);
    }

    public function __unset($name)
    {
        unset($this->attributes[$name]);
    }

    /**
     * @todo
     */
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

    protected function getMutatorMethod($type, $name)
    {
        return $type . str_replace('_', '', ucwords($name, '_')) . 'Attribute';
    }

    protected function hasMutator($type, $name)
    {
        $method = $this->getMutatorMethod($type, $name);
        return method_exists($this, $method);
    }

    protected function mutate($type, $name, $value)
    {
        $method = $this->getMutatorMethod($type, $name);
        return $this->$method($value);
    }

    public function getAttribute($name)
    {
        if (isset($this->attributes[$name])) {
            $value = $this->attributes[$name];

            if ($this->hasMutator('get', $name)) {
                $value = $this->mutate('get', $name, $value);
            }

            return $value;
        }

        return null;
    }

    public function setAttribute($name, $value)
    {
        if ($this->hasMutator('set', $name)) {
            $value = $this->mutate('set', $name, $value);
        }

        $this->attributes[$name] = $value;
    }

    public function hasAttribute($name) {
        return isset($this->attributes[$name]);
    }

    public function getAttributes()
    {
        $attributes = [];
        foreach (array_keys($this->attributes) as $name) {
            $attributes[$name] = $this->getAttribute($name);
        }
        return $attributes;
    }

    protected function fill($attributes)
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

    public function hidden(): array
    {
        return [];
    }
}