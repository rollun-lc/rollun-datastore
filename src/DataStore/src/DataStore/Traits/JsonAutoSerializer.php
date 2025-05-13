<?php

namespace rollun\datastore\DataStore\Traits;

use rollun\utils\Json\Serializer;

/**
 * Trait JsonAutoSerializer
 * @package rollun\datastore\DataStore\Traits
 *
 * // Tutor usages
 *
 * class TestDs extends DbTable {
 *  use JsonAutoSerializer;
 *
 *      public function getJsonField() {
 *          return ["json_data"];
 *      }
 *
 *      public function query(...) {
 *          return array_map([$this, 'unpackJson'], parent::query(..));
 *      }
 *      public function create(...) {
 *          return parent::create($this->pack(...));
 *      }
 * }
 *
 */
trait JsonAutoSerializer
{
    /**
     * @return array
     */
    abstract public function getJsonField(): array;

    /**
     * Decode sting fields to json
     * @param $item
     * @return mixed
     */
    public function unpackJson($item)
    {
        foreach ($this->getJsonField() as $field) {
            if (isset($item[$field])) {
                $item[$field] = Serializer::jsonUnserialize($item[$field]);
            }
        }
        return $item;
    }

    /**
     * Encode json fields to string
     * @param $item
     * @return mixed
     */
    public function packJson($item)
    {
        foreach ($this->getJsonField() as $field) {
            if (isset($item[$field])) {
                $item[$field] = Serializer::jsonSerialize($item[$field]);
            }
        }
        return $item;
    }


}
