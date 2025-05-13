<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Traits;

use rollun\datastore\DataStore\DataStoreException;
use rollun\utils\IdGenerator;

/**
 * Trait AutoIdGeneratorTrait add to DataStore features auto generate id
 * @package rollun\datastore\DataStore\Traits
 */
trait AutoIdGeneratorTrait
{
    /**
     * @var IdGenerator
     */
    protected $idGenerator;

    /**
     * Generates an arbitrary length string of cryptographic random bytes
     *
     * @return string
     * @throws DataStoreException
     */
    protected function generateId()
    {
        trigger_error(AutoIdGeneratorTrait::class . ' trait is deprecated', E_USER_DEPRECATED);

        $tryCount = 0;

        do {
            $id = $this->idGenerator->generate();
            $tryCount++;

            if ($tryCount >= $this->getIdGenerateMaxTry()) {
                throw new DataStoreException("Can't generate id.");
            }
        } while ($this->has($id));

        return $id;
    }

    /**
     * Return true if item with that 'id' is present.
     *
     * @param $id
     * @return mixed
     */
    abstract public function has($id);

    /**
     * Return primary key identifier
     * Return "id" by default
     *
     * @see ReadInterface::DEF_ID
     * @return string "id" by default
     */
    abstract protected function getIdentifier();

    /**
     * Return count of max tyr for generate ID.
     *
     * @return int
     */
    protected function getIdGenerateMaxTry()
    {
        return 5;
    }

    /**
     * Generate id to item
     *
     * @param array $itemData
     * @return array
     * @throws DataStoreException
     */
    protected function prepareItem(array $itemData)
    {
        if (!isset($itemData[$this->getIdentifier()])) {
            $itemData[$this->getIdentifier()] = $this->generateId();
        }

        return $itemData;
    }
}
