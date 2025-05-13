<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore;

use rollun\datastore\DataStore\ConditionBuilder\PhpConditionBuilder;

/**
 * Represent php array as datastore
 * All items store in protected property $item (actually it is a RAM)
 * To easy search item id matches $item key
 *
 * Example:
 * [
 *      1 => [
 *          // identifier can be another
 *          'id' => 1,
 *          'name' => 'name1',
 *      ],
 *      37 => [
 *          // identifier can be another
 *          'id' => 37,
 *          'name' => 'name2',
 *      ],
 * ]
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
    protected $columns;

    /**
     * Memory constructor.
     * @param array $columns
     */
    public function __construct(array $columns = [])
    {
        if (!count($columns)) {
            trigger_error("Array of required columns is not specified", E_USER_DEPRECATED);
        }

        $this->columns = $columns;
        $this->conditionBuilder = new PhpConditionBuilder();
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
        if (!$this->wasCalledFrom(DataStoreAbstract::class, 'rewrite')
            && !$this->wasCalledFrom(DataStoreAbstract::class, 'rewriteMultiple')
            && $rewriteIfExist
        ) {
            trigger_error("Option 'rewriteIfExist' is no more use", E_USER_DEPRECATED);
        }

        $this->checkOnExistingColumns($itemData);
        $identifier = $this->getIdentifier();
        $id = $itemData[$identifier] ?? null;

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

        $this->items[$id] = array_merge([$identifier => $id], $itemData);

        return $this->items[$id];
    }

    /**
     * {@inheritdoc}
     */
    public function update($itemData, $createIfAbsent = false)
    {
        if ($createIfAbsent) {
            trigger_error("Option 'createIfAbsent' is no more use", E_USER_DEPRECATED);
        }

        $this->checkOnExistingColumns($itemData);
        $identifier = $this->getIdentifier();

        if (!isset($itemData[$identifier])) {
            throw new DataStoreException('Item must has primary key');
        }

        $this->checkIdentifierType($itemData[$identifier]);
        $id = $itemData[$identifier];

        if (isset($this->items[$id])) {
            foreach ($itemData as $field => $value) {
                $this->items[$id][$field] = $value;
            }

            unset($itemData[$id]);
        } else {
            if ($createIfAbsent) {
                $this->items[$id] = $itemData;
            } else {
                throw new DataStoreException("Item doesn't exist with id = $id");
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
        trigger_error("Datastore is no more iterable", E_USER_DEPRECATED);

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
     * @param $itemData
     * @return mixed
     * @throws DataStoreException
     */
    protected function checkOnExistingColumns($itemData)
    {
        if (!count($this->columns)) {
            return $itemData;
        }

        foreach ($itemData as $field => $value) {
            if (!in_array($field, $this->columns)) {
                throw new DataStoreException("Undefined field '$field' in data store");
            }
        }

        return $itemData;
    }
}
