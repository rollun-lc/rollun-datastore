<?php

namespace rollun\datastore\DataStore\Model;


use rollun\datastore\DataStore\Interfaces\ModelInterface;
use rollun\datastore\DataStore\Model\ModelDataStore;

abstract class Model implements ModelInterface
{
    /**
     * @var ModelDataStore
     */
    protected $dataStore;

    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * Model constructor.
     * @param ModelDataStore|null $dataStore
     */
    public function __construct()
    {

    }

    /**
     * @param ModelDataStore $dataStore
     */
    public function setDataStore(?ModelDataStore $dataStore)
    {
        $this->dataStore = $dataStore;
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
        return $this->getAttributes();
    }
}