<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Iterators;

use Graviton\RqlParser\Node;
use Graviton\RqlParser\Query;
use Graviton\RqlParser\Node\Query\ScalarOperator;
use rollun\datastore\DataStore\Interfaces\ReadInterface;

/**
 * Outer iterator for rollun\datastore\DataStore\Read\ReadInterface objects
 *
 * Class DataStoreIterator
 * @package rollun\datastore\DataStore\Iterators
 */
class DataStoreIterator implements \Iterator
{
    /**
     * Pointer for current item in iteration
     *
     * @see Iterator
     * @var mixed $index
     */
    protected $index = null;

    /**
     * @see Iterator
     * @var ReadInterface $dataStores
     */
    protected $dataStore;

    /**
     * @see Iterator
     * @param ReadInterface $dataStore
     */
    public function __construct(ReadInterface $dataStore)
    {
        $this->dataStore = $dataStore;
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $identifier = $this->dataStore->getIdentifier();
        $query = new Query();
        $selectNode = new Node\SelectNode([$identifier]);
        $query->setSelect($selectNode);
        $sortNode = new Node\SortNode([$identifier => 1]);
        $query->setSort($sortNode);
        $limitNode = new Node\LimitNode(1, 0);
        $query->setLimit($limitNode);
        $queryArray = $this->dataStore->query($query);
        $this->index = $queryArray === [] ? null : $queryArray[0][$identifier];
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        $result = isset($this->index) ? $this->dataStore->read($this->index) : null;

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->index;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $identifier = $this->dataStore->getIdentifier();
        $query = new Query();
        $selectNode = new Node\SelectNode([$identifier]);
        $query->setSelect($selectNode);
        $sortNode = new Node\SortNode([$identifier => 1]);
        $query->setSort($sortNode);
        $limitNode = new Node\LimitNode(1, 0);
        $query->setLimit($limitNode);
        $gtNode = new ScalarOperator\GtNode($identifier, $this->index);
        $query->setQuery($gtNode);
        $queryArray = $this->dataStore->query($query);
        $this->index = $queryArray === [] ? null : $queryArray[0][$identifier];
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return isset($this->index) && ($this->dataStore->read($this->index) !== null);
    }
}
