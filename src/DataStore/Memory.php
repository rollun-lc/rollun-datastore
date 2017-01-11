<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace rollun\datastore\DataStore;

use rollun\datastore\DataStore\DataStoreAbstract;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\ConditionBuilder\PhpConditionBuilder;

/**
 * DataStores as array
 *
 * @todo delete string 74
 * @see http://en.wikipedia.org/wiki/Create,_read,_update_and_delete
 * @category   rest
 * @package    zaboy
 */
class Memory extends DataStoreAbstract
{

    /**
     * @var array Collected items
     */
    protected $items = array();

    public function __construct()
    {
        $this->conditionBuilder = new PhpConditionBuilder;
    }

//** Interface "rollun\datastore\DataStore\Interfaces\ReadInterface" **/

    /**
     * {@inheritdoc}
     *
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

// ** Interface "rollun\datastore\DataStore\Interfaces\DataStoresInterface"  **/

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function create($itemData, $rewriteIfExist = false)
    {
        $identifier = $this->getIdentifier();
        if (!isset($itemData[$identifier])) {
            $this->items[] = $itemData;
            $itemsKeys = array_keys($this->items);
            $id = array_pop($itemsKeys);
        } elseif (!$rewriteIfExist && isset($this->items[$itemData[$identifier]])) {
            throw new DataStoreException('Item is already exist with "id" =  ' . $itemData[$identifier]);
        } else {
            $id = $itemData[$identifier];
            $this->checkIdentifierType($id);
            $this->items[$id] = array_merge(array($identifier => $id), $itemData);
        }
        $this->items[$id] = array_merge(array($identifier => $id), $itemData);
        return $this->items[$id];
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function update($itemData, $createIfAbsent = false)
    {
        $identifier = $this->getIdentifier();
        if (!isset($itemData[$identifier])) {
            throw new DataStoreException('Item must has primary key');
        }
        $id = $itemData[$identifier];
        $this->checkIdentifierType($id);

        switch (true) {
            case!isset($this->items[$id]) && !$createIfAbsent:
                $errorMsg = 'Can\'t update item with "id" = ' . $id;
                throw new DataStoreException($errorMsg);
            case!isset($this->items[$id]) && $createIfAbsent:
                $this->items[$id] = array_merge(array($identifier => $id), $itemData);
                break;
            case isset($this->items[$id]):
                unset($itemData[$id]);
                $this->items[$id] = array_merge($this->items[$id], $itemData);
                break;
        }
        return $this->items[$id];
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function delete($id)
    {

        $this->checkIdentifierType($id);
        if (isset($this->items[$id])) {
            $item = $this->items[$id];
            unset($this->items[$id]);

        }
        return isset($item) ? $item : null;
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function deleteAll()
    {
        $deletedItemsCount = count($this->items);
        $this->items = array();
        return $deletedItemsCount;
    }

// ** Interface "/Coutable"  **/

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->items);
    }

// ** Interface "/IteratorAggregate"  **/

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->items);
    }

// ** protected  **/

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    protected function getKeys()
    {
        return array_keys($this->items);
    }

}
