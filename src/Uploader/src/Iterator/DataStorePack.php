<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\uploader\Iterator;

use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use SeekableIterator;
use Graviton\RqlParser\Node\LimitNode;
use Graviton\RqlParser\Node\Query\ScalarOperator\GtNode;
use Graviton\RqlParser\Node\SortNode;
use Graviton\RqlParser\Query;

class DataStorePack implements SeekableIterator
{
    /**
     * @var int
     */
    protected $limit;

    /**
     * @var DataStoresInterface
     */
    protected $dataStore;

    /**
     * @var array
     */
    protected $current = null;

    /**
     * DataStoreIterator constructor.
     * @param DataStoresInterface $dataStore
     * @param int $limit
     */
    public function __construct(DataStoresInterface $dataStore, $limit = 100)
    {
        $this->dataStore = $dataStore;
        $this->limit = $limit;
        $initItem = $this->dataStore->query($this->getInitQuery());

        if (!empty($initItem)) {
            $this->current = current($initItem);
        }
    }

    /**
     * @return Query
     */
    protected function getInitQuery()
    {
        $query = new Query();
        $query->setLimit(new LimitNode(1));
        $query->setSort(new SortNode([$this->dataStore->getIdentifier() => SortNode::SORT_ASC]));

        return $query;
    }

    /**
     * Return query with limit and offset
     */
    protected function getQuery()
    {
        $query = new Query();

        if ($this->valid()) {
            $query->setQuery(new GtNode($this->dataStore->getIdentifier(), $this->key()));
        }

        $query->setLimit(new LimitNode($this->limit));
        $query->setSort(new SortNode([$this->dataStore->getIdentifier() => SortNode::SORT_ASC]));

        return $query;
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->current;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $data = $this->dataStore->query($this->getQuery());

        foreach ($data as $datum) {
            $this->current = $datum;
            yield;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->current[$this->dataStore->getIdentifier()];
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return (
            !is_null($this->current) &&
            $this->dataStore->has($this->current[$this->dataStore->getIdentifier()])
        );
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->current = null;
    }

    /**
     * {@inheritdoc}
     */
    public function seek($position)
    {
        $item = $this->dataStore->read($position);

        if (!isset($item) || empty($item)) {
            throw new \InvalidArgumentException("Position not valid or not found.");
        }

        $this->current = $item;
    }
}
