<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore;

use Xiag\Rql\Parser\Query;
use rollun\datastore\DataSource\DataSourceInterface;
use rollun\datastore\DataStore\Interfaces\RefreshableInterface;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;

class Cacheable implements DataStoresInterface, RefreshableInterface
{
    /** @var  DataStoresInterface */
    protected $cashStore;

    /** @var  DataSourceInterface */
    protected $dataSource;

    public function __construct(DataSourceInterface $dataSource, DataStoresInterface $cashStore = null)
    {
        $this->dataSource = $dataSource;

        if (isset($cashStore)) {
            $this->cashStore = $cashStore;
        } else {
            $this->cashStore = new Memory();
        }
    }

    public function query(Query $query)
    {
        return $this->cashStore->query($query);
    }

    /**
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return \Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator()
    {
        return $this->cashStore->getIterator();
    }

    /**
     * Return primary key
     *
     * Return "id" by default
     *
     * @see DEF_ID
     * @return string "id" by default
     */
    public function getIdentifier()
    {
        return $this->cashStore->getIdentifier();
    }

    /**
     * Return Item by id
     *
     * Method return null if item with that id is absent.
     * Format of Item - Array("id"=>123, "field1"=value1, ...)
     *
     * @param int|string $id PrimaryKey
     * @return array|null
     */
    public function read($id)
    {
        return $this->cashStore->read($id);
    }

    /**
     * Return true if item with that id is present.
     *
     * @param int|string $id PrimaryKey
     * @return bool
     */
    public function has($id)
    {
        return $this->cashStore->has($id);
    }

    public function refresh()
    {
        $this->cashStore->deleteAll();
        $all = $this->dataSource->getAll();

        if ($all instanceof \Traversable or is_array($all)) {
            foreach ($all as $item) {
                $this->cashStore->create($item, true);
            }
        } else {
            throw new DataStoreException("Not return data by DataSource");
        }
    }

    /**
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count()
    {
        return $this->cashStore->count();
    }

    /**
     * By default, insert new (by create) item.
     *
     * It can't overwrite existing item by default.
     * You can get item "id" for created item us result this function.
     *
     * If  $item["id"] !== null, item set with that id.
     * If item with same id already exist - method will throw exception,
     * but if $rewriteIfExist = true item will be rewrites.<br>
     *
     * If $item["id"] is not set or $item["id"]===null,
     * item will be insert with autoincrement PrimaryKey.<br>
     *
     * @param array $itemData associated array with or without PrimaryKey
     * @param bool $rewriteIfExist
     * @return int|null|string "id" for created item or null if item wasn't created
     * @throws DataStoreException
     */
    public function create($itemData, $rewriteIfExist = false)
    {
        if ($rewriteIfExist) {
            trigger_error("Option 'rewriteIfExist' is no more use", E_USER_DEPRECATED);
        }

        if (method_exists($this->dataSource, "create")) {
            return $this->dataSource->create($itemData, $rewriteIfExist);
        } else {
            throw new DataStoreException("Refreshable don't haw method create");
        }
    }

    /**
     * By default, update existing Item.
     *
     * If item with PrimaryKey == $item["id"] is existing in store, item will update.
     * Fields which don't present in $item will not change in item in store.<br>
     * Method will return updated item<br>
     * <br>
     * If $item["id"] isn't set - method will throw exception.<br>
     * <br>
     * If item with PrimaryKey == $item["id"] is absent - method  will throw exception,<br>
     * but if $createIfAbsent = true item will be created and method return inserted item<br>
     * <br>
     *
     * @param array $itemData associated array with PrimaryKey
     * @param bool $createIfAbsent
     * @return array updated item or inserted item
     * @throws DataStoreException
     */
    public function update($itemData, $createIfAbsent = false)
    {
        if ($createIfAbsent) {
            trigger_error("Option 'createIfAbsent' is no more use.", E_USER_DEPRECATED);
        }

        if (method_exists($this->dataSource, "update")) {
            return $this->dataSource->update($itemData, $createIfAbsent);
        } else {
            throw new DataStoreException("Refreshable don't haw method update");
        }
    }

    /**
     * Delete Item by id. Method do nothing if item with that id is absent.
     *
     * @param int|string $id PrimaryKey
     * @return int number of deleted items: 0 , 1 or null if object doesn't support it
     * @throws DataStoreException
     */
    public function publicdelete($id)
    {
        if (method_exists($this->dataSource, "delete")) {
            return $this->dataSource->delete($id);
        } else {
            throw new DataStoreException("Refreshable don't haw method delete");
        }
    }

    /**
     * Delete all Items.
     * @return int number of deleted items or null if object doesn't support it
     * @throws DataStoreException
     */
    public function deleteAll()
    {
        if (method_exists($this->dataSource, "deleteAll")) {
            return $this->dataSource->deleteAll();
        } else {
            throw new DataStoreException("Refreshable don't haw method deleteAll");
        }
    }

    /**
     * Delete Item by 'id'. Method do nothing if item with that id is absent.
     *
     * @param int|string $id PrimaryKey
     * @return array from elements or null is not support
     * @throws DataStoreException
     */
    public function delete($id)
    {
        if (method_exists($this->dataSource, "delete")) {
            return $this->dataSource->delete($id);
        } else {
            throw new DataStoreException("Refreshable don't haw method delete");
        }
    }
}
