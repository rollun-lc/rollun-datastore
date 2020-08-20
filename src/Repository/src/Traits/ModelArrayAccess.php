<?php


namespace rollun\repository\Traits;


use \Exception;

/**
 * Trait ModelArrayAccess
 *
 * @package rollun\repository\Traits
 */
trait ModelArrayAccess
{
    /**
     * @todo
     * @inheritDoc
     */
    public function offsetExists($offset)
    {
        try {
            $this->getAttribute($offset);
            return true;
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($offset)
    {
        return $this->getAttribute($offset);
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value)
    {
        $this->setAttribute($offset, $value);
    }

    /**
     * @todo
     */
    public function offsetUnset($offset)
    {
        unset($this->attributes[$offset]);
    }
}