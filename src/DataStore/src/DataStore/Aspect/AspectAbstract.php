<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Aspect;

use rollun\datastore\DataStore\Interfaces\DataStoreInterface;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use Xiag\Rql\Parser\Query;

/**
 * Class AspectAbstract
 *
 * This is wrapper for any type of datastore which allows to do 'pre' and 'post' actions
 * for each method of the DataStoresInterface.
 *
 * The class is NOT abstract. It is so named because in this view it does nothing and have no difference at work
 * with usual datastore any type.
 *
 * @see AspectAbstractFactory
 * @package rollun\datastore\DataStore\Aspect
 */
class AspectAbstract implements DataStoresInterface, DataStoreInterface
{
    /** @var DataStoresInterface $dataStore */
    protected $dataStore;

    /**
     * AspectDataStoreAbstract constructor.
     *
     * @param DataStoresInterface $dataStore
     */
    public function __construct(DataStoresInterface $dataStore)
    {
        $this->dataStore = $dataStore;
    }

    /**
     * The pre-aspect for "getIterator".
     *
     * By default does nothing
     */
    protected function preGetIterator()
    {
    }

    /**
     *
     * {@inheritdoc}
     */
    public function getIterator()
    {
        $this->preGetIterator();
        $iterator = $this->dataStore->getIterator();

        return $this->postGetIterator($iterator);
    }

    /**
     * The post-aspect for "getIterator"
     *
     * By default does nothing
     *
     * @param \Traversable $iterator
     * @return \Traversable
     */
    protected function postGetIterator(\Traversable $iterator)
    {
        return $iterator;
    }

    /**
     * The pre-aspect for "create".
     *
     * By default does nothing
     *
     * @param $itemData
     * @param bool|false $rewriteIfExist
     * @return array
     */
    protected function preCreate($itemData, $rewriteIfExist = false)
    {
        return $itemData;
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function create($itemData, $rewriteIfExist = false)
    {
        $newData = $this->preCreate($itemData, $rewriteIfExist);
        $result = $this->dataStore->create($newData, $rewriteIfExist);

        return $this->postCreate($result, $newData, $rewriteIfExist);
    }

    /**
     * The post-aspect for "create"
     *
     * By default does nothing
     *
     * @param $result
     * @param $itemData
     * @param $rewriteIfExist
     * @return mixed
     */
    protected function postCreate($result, $itemData, $rewriteIfExist)
    {
        return $result;
    }

    /**
     * The pre-aspect for "update".
     *
     * By default does nothing
     *
     * @param $itemData
     * @param bool|false $createIfAbsent
     * @return array
     */
    protected function preUpdate($itemData, $createIfAbsent = false)
    {
        return $itemData;
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function update($itemData, $createIfAbsent = false)
    {
        $newData = $this->preUpdate($itemData, $createIfAbsent);
        $result = $this->dataStore->update($newData, $createIfAbsent);

        return $this->postUpdate($result, $newData, $createIfAbsent);
    }

    /**
     * The post-aspect for "update"
     *
     * By default does nothing
     *
     * @param mixed $result
     * @param $itemData
     * @param $createIfAbsent
     * @return mixed
     */
    protected function postUpdate($result, $itemData, $createIfAbsent)
    {
        return $result;
    }

    /**
     * The pre-aspect for "delete".
     *
     * By default does nothing
     *
     * @param $id
     */
    protected function preDelete($id)
    {
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function delete($id)
    {
        $this->preDelete($id);
        $result = $this->dataStore->delete($id);

        return $this->postDelete($result, $id);
    }

    /**
     * The post-aspect for "delete"
     *
     * By default does nothing
     *
     * @param mixed $result
     * @param $id
     * @return mixed
     */
    protected function postDelete($result, $id)
    {
        return $result;
    }

    /**
     * The pre-aspect for "deleteAll".
     *
     * By default does nothing
     */
    protected function preDeleteAll()
    {
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function deleteAll()
    {
        $this->preDeleteAll();
        $result = $this->dataStore->deleteAll();

        return $this->postDeleteAll($result);
    }

    /**
     * The post-aspect for "deleteAll"
     *
     * By default does nothing
     *
     * @param mixed $result
     * @return mixed
     */
    protected function postDeleteAll($result)
    {
        return $result;
    }

    /**
     * The pre-aspect for "getIdentifier".
     *
     * By default does nothing
     */
    protected function preGetIdentifier()
    {
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        $this->preGetIdentifier();
        $result = $this->dataStore->getIdentifier();

        return $this->postGetIdentifier($result);
    }

    /**
     * The post-aspect for "getIdentifier"
     *
     * By default does nothing
     *
     * @param mixed $result
     * @return mixed
     */
    protected function postGetIdentifier($result)
    {
        return $result;
    }

    /**
     * The pre-aspect for "read".
     *
     * By default does nothing
     *
     * @param $id
     */
    protected function preRead($id)
    {
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function read($id)
    {
        $this->preRead($id);
        $result = $this->dataStore->read($id);

        return $this->postRead($result, $id);
    }

    /**
     * The post-aspect for "read"
     *
     * By default does nothing
     *
     * @param mixed $result
     * @param $id
     * @return mixed
     */
    protected function postRead($result, $id)
    {
        return $result;
    }

    /**
     * The pre-aspect for "has".
     *
     * By default does nothing
     *
     * @param $id
     */
    protected function preHas($id)
    {
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function has($id)
    {
        $this->preHas($id);
        $result = $this->dataStore->has($id);
        $this->postHas($result, $id);

        return $result;
    }

    /**
     * The post-aspect for "has"
     *
     * By default does nothing
     *
     * @param mixed $result
     * @param $id
     * @return mixed
     */
    protected function postHas($result, $id)
    {
        return $result;
    }

    /**
     * The pre-aspect for "query".
     *
     * By default does nothing
     *
     * @param Query $query
     * @return Query
     */
    protected function preQuery(Query $query)
    {
        return $query;
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function query(Query $query)
    {
        $newQuery = $this->preQuery($query);
        $result = $this->dataStore->query($newQuery);

        return $this->postQuery($result, $newQuery);
    }

    /**
     * The post-aspect for "query"
     *
     * By default does nothing
     *
     * @param mixed $result
     * @param Query $query
     * @return mixed
     */
    protected function postQuery($result, Query $query)
    {
        return $result;
    }

    /**
     * The pre-aspect for "count".
     *
     * By default does nothing
     */
    protected function preCount()
    {
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function count()
    {
        $this->preCount();
        $result = $this->dataStore->count();

        return $this->postCount($result);
    }

    /**
     * The post-aspect for "count"
     *
     * By default does nothing
     *
     * @param mixed $result
     * @return mixed
     */
    protected function postCount($result)
    {
        return $result;
    }

    /**
     * @param mixed $records
     *
     * @return mixed
     */
    protected function preMultiCreate($records)
    {
        return $records;
    }

    /**
     * @inheritDoc
     */
    public function multiCreate($records)
    {
        $records = $this->preMultiCreate($records);

        $result = $this->dataStore->multiCreate($records);

        return $this->postMultiCreate($result, $records);
    }

    /**
     * @param mixed $result
     * @param mixed $records
     *
     * @return mixed
     */
    protected function postMultiCreate($result, $records)
    {
        return $result;
    }

    /**
     * @param mixed $records
     *
     * @return mixed
     */
    protected function preMultiUpdate($records)
    {
        return $records;
    }

    /**
     * @inheritDoc
     */
    public function multiUpdate($records)
    {
        $records = $this->preMultiUpdate($records);

        $result = $this->dataStore->multiUpdate($records);

        return $this->postMultiUpdate($result, $records);
    }

    /**
     * @param mixed $result
     * @param mixed $records
     *
     * @return mixed
     */
    protected function postMultiUpdate($result, $records)
    {
        return $result;
    }

    /**
     * @param mixed $record
     * @param Query $query
     */
    protected function preQueriedUpdate(&$record, Query $query)
    {
    }

    /**
     * @inheritDoc
     */
    public function queriedUpdate($record, Query $query)
    {
        $this->preQueriedUpdate($record, $query);

        $result = $this->dataStore->queriedUpdate($record, $query);

        return $this->postQueriedUpdate($result, $record, $query);
    }

    /**
     * @param mixed $result
     * @param mixed $record
     * @param Query $query
     *
     * @return mixed
     */
    protected function postQueriedUpdate($result, $record, Query $query)
    {
        return $result;
    }

    /**
     * @param mixed $record
     *
     * @return mixed
     */
    protected function preRewrite($record)
    {
        return $record;
    }

    /**
     * @inheritDoc
     */
    public function rewrite($record)
    {
        $record = $this->preRewrite($record);

        $result = $this->dataStore->rewrite($record);

        return $this->postRewrite($result, $record);
    }

    /**
     * @param mixed $result
     * @param mixed $record
     *
     * @return mixed
     */
    protected function postRewrite($result, $record)
    {
        return $result;
    }

    /**
     * @param Query $query
     */
    protected function preQueriedDelete(Query $query)
    {
    }

    /**
     * @inheritDoc
     */
    public function queriedDelete(Query $query)
    {
        $this->preQueriedDelete($query);

        $result = $this->dataStore->queriedDelete($query);

        return $this->postQueriedDelete($result, $query);
    }

    /**
     * @param mixed $result
     * @param Query $query
     *
     * @return mixed
     */
    protected function postQueriedDelete($result, Query $query)
    {
        return $result;
    }
}
