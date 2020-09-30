<?php

namespace rollun\repository;


use ArrayAccess;
use rollun\repository\Interfaces\ModelHiddenFieldInterface;
use rollun\repository\Interfaces\ModelInterface;
use rollun\repository\Traits\ModelArrayAccess;
use rollun\repository\Traits\ModelCastingTrait;
use rollun\repository\Traits\ModelDataTime;

/**
 * Class ModelAbstract
 *
 * @package rollun\repository
 */
abstract class ModelAbstract implements ModelInterface, ModelHiddenFieldInterface, ArrayAccess
{
    use ModelArrayAccess;
    use ModelDataTime;
    use ModelCastingTrait;

    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * @var array
     */
    protected $original = [];

    /**
     * @var bool
     */
    protected $exists = false;

    protected $casting = [];

    /**
     * ModelAbstract constructor.
     *
     * @param array $attributes
     * @param false $exists
     */
    public function __construct($attributes = [], $exists = false)
    {
        $this->updateOriginal();

        $this->fill($attributes);

        //$this->original = $this->attributes;

        //$this->exists = $exists;

        $this->setExists($exists);
    }

    public function updateOriginal()
    {
        $this->original = $this->attributes;
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->setAttribute($name, $value);
    }

    /**
     * @param $name
     *
     * @return mixed|null
     */
    public function __get($name)
    {
        return $this->getAttribute($name);
    }

    /**
     * @param $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return $this->hasAttribute($name);
    }

    /**
     * @param $name
     */
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

    /**
     *
     */
    public function clone()
    {
        $attributes = $this->attributes;
        $model = clone $this;
        $model->fill($attributes);
    }

    /**
     * @param $type
     * @param $name
     *
     * @return string
     */
    protected function getMutatorMethod($type, $name)
    {
        return $type . str_replace('_', '', ucwords($name, '_')) . 'Attribute';
    }

    /**
     * @param $type
     * @param $name
     *
     * @return bool
     */
    protected function hasMutator($type, $name)
    {
        $method = $this->getMutatorMethod($type, $name);
        return method_exists($this, $method);
    }

    /**
     * @param $type
     * @param $name
     * @param $value
     *
     * @return mixed
     */
    protected function mutate($type, $name, $value)
    {
        $method = $this->getMutatorMethod($type, $name);
        return $this->$method($value);
    }

    /**
     * @param $name
     *
     * @return mixed|null
     */
    public function getAttribute($name)
    {
        if (isset($this->attributes[$name])) {
            $value = $this->attributes[$name];

            if ($this->hasMutator('get', $name)) {
                $value = $this->mutate('get', $name, $value);
            }

            if (array_key_exists($name, $this->casting)) {
                $value = $this->cast($name, $value);
            }

            return $value;
        }

        return null;
    }

    /**
     * @param $name
     * @param $value
     */
    public function setAttribute($name, $value)
    {
        if ($this->hasMutator('set', $name)) {
            $value = $this->mutate('set', $name, $value);
        }

        $this->attributes[$name] = $value;
    }

    /**
     * @param $name
     *
     * @return bool
     */
    public function hasAttribute($name) {
        return isset($this->attributes[$name]);
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
        /*$attributes = [];
        foreach (array_keys($this->attributes) as $name) {
            $attributes[$name] = $this->getAttribute($name);
        }
        return $attributes;*/
    }

    /**
     * @param $attributes
     */
    public function fill($attributes)
    {
        foreach ($attributes as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $attributes = $this->getAttributes();
        foreach ($attributes as $key => $attribute) {
            if (in_array($key, $this->hidden())) {
                unset($attributes[$key]);
            }
        }
        return $attributes;
    }

    /**
     * @return array
     */
    public function hidden(): array
    {
        return [];
    }

    /**
     * @return bool
     */
    public function isChanged(): bool
    {
        //return $this->attributes !== $this->original;
        foreach ($this->attributes as $name => $value) {
            if ($this->isChangedAttribute($name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @todo
     *
     * @param $name
     *
     * @return bool
     */
    protected function isChangedAttribute($name)
    {
        if (!array_key_exists($name, $this->original)) {
            return true;
        }

        if ($this->attributes[$name] != $this->original[$name]) {
            return true;
        }

        return false;
    }

    /**
     * @return array
     */
    public function getChanges(): array
    {
        //return array_diff($this->attributes, $this->original);
        $changes = [];
        foreach ($this->attributes as $name => $value) {
            if ($this->isChangedAttribute($name)) {
                $changes[$name] = $value;
            }
        }

        return $changes;
    }

    /**
     * @return bool
     */
    public function isExists(): bool
    {
        return $this->exists;
    }

    /**
     * @todo
     *
     * @param bool $exists
     */
    public function setExists(bool $exists): void
    {
        $this->exists = $exists;

        if ($exists) {
            $this->updateOriginal();
        }
    }
}