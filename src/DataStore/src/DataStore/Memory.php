<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore;

use rollun\datastore\DataStore\ConditionBuilder\PhpConditionBuilder;

/**
 * Represent php array as datastore
 *
 * Class Memory
 * @package rollun\datastore\DataStore
 */
class Memory extends DataStoreAbstract
{
    /**
     * Collected items
     *
     * @var array
     */
    protected $items = [];

    /**
     * Required fields
     *
     * @var array
     */
    protected $fields;

    /**
     * Memory constructor.
     * @param array $fields
     */
    public function __construct(array $fields = [])
    {
        if (!count($fields)) {
            trigger_error("Array of required columns is not specified", E_USER_DEPRECATED);
        }

        $this->fields = $fields;
        $this->conditionBuilder = new PhpConditionBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function read($id)
    {
        $this->checkIdentifierType($id);
        if (isset($this->items[$id])) {
            return $this->items[$id];
        } else {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function create($itemData, $rewriteIfExist = false)
    {
        // TODO: move to abstract
        /*if ($rewriteIfExist) {
            trigger_error("Option 'rewriteIfExist' is no more use", E_USER_DEPRECATED);
        }*/

        $identifier = $this->getIdentifier();
        $id = isset($itemData[$identifier]) ? $itemData[$identifier] : null;

        if ($id) {
            if (isset($this->items[$id]) && !$rewriteIfExist) {
                throw new DataStoreException("Item with id '{$itemData[$identifier]}' already exist");
            }

            $this->checkIdentifierType($id);
        } else {
            $this->items[] = $itemData;
            $itemsKeys = array_keys($this->items);
            $id = array_pop($itemsKeys);
        }

        $this->items[$id] = array_merge(array($identifier => $id), $itemData);

        return $this->items[$id];
    }

    /**
     * {@inheritdoc}
     */
    public function update($itemData, $createIfAbsent = false)
    {
        // TODO: move to abstract
        /*if ($createIfAbsent) {
            trigger_error("Option 'createIfAbsent' is no more use", E_USER_DEPRECATED);
        }*/

        $identifier = $this->getIdentifier();

        if (!isset($itemData[$identifier])) {
            throw new DataStoreException('Item must has primary key');
        }

        $this->checkIdentifierType($itemData[$identifier]);
        $identifier = $this->getIdentifier();
        $id = $itemData[$identifier];

        if (isset($this->items[$id])) {
            unset($itemData[$id]);
            $this->items[$id] = $itemData;
        } else {
            if ($createIfAbsent) {
                $this->items[$id] = $itemData;
            } else {
                throw new DataStoreException("Item with id '$id' doesn't exist");
            }
        }

        return $this->items[$id];
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        $this->checkIdentifierType($id);

        if (isset($this->items[$id])) {
            $item = $this->items[$id];
            unset($this->items[$id]);

            return $item;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAll()
    {
        $deletedItemsCount = count($this->items);
        $this->items = [];

        return $deletedItemsCount;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * {@inheritdoc}
     */
    protected function getKeys()
    {
        return array_keys($this->items);
    }

    /**
     * Check if item data match with expected columns (data store fields)
     *
     * @param $itemData
     * @return mixed
     */
    protected function validateItemData($itemData)
    {
        if (!count($this->fields)) {
            return $itemData;
        }

        foreach ($itemData as $field => $value) {
            if (!in_array($field, $this->fields)) {
                throw new DataStoreException("Undefined field '$field' in data store");
            }
        }

        return $itemData;
    }
}
