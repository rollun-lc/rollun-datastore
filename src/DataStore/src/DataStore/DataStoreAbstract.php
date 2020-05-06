<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore;

use rollun\datastore\DataStore\ConditionBuilder\ConditionBuilderAbstract;
use rollun\datastore\DataStore\Interfaces\DataStoreInterface;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use rollun\datastore\DataStore\Iterators\DataStoreIterator;
use rollun\datastore\Rql\Node\AggregateFunctionNode;
use rollun\datastore\Rql\Node\AggregateSelectNode;
use rollun\datastore\Rql\RqlQuery;
use Xiag\Rql\Parser\Node;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\EqNode;
use Xiag\Rql\Parser\Node\SortNode;
use Xiag\Rql\Parser\Query;

/**
 * Class DataStoreAbstract
 * @package rollun\datastore\DataStore
 */
abstract class DataStoreAbstract implements DataStoresInterface, DataStoreInterface
{
    /**
     * @var ConditionBuilderAbstract
     */
    protected $conditionBuilder;

    /**
     * {@inheritdoc}
     */
    public function has($id)
    {
        return !(empty($this->read($id)));
    }

    /**
     * {@inheritdoc}
     */
    public function read($id)
    {
        $identifier = $this->getIdentifier();
        $this->checkIdentifierType($id);
        $query = new Query();
        $eqNode = new EqNode($identifier, $id);
        $query->setQuery($eqNode);
        $queryResult = $this->query($query);

        if (empty($queryResult)) {
            return null;
        } else {
            return $queryResult[0];
        }
    }

    protected function getNext($id)
    {
        $isNext = false;
        $iterator = $this->getIterator();

        if (is_null($id)) {
            $iterator->rewind();
            return $iterator->current();
        }

        foreach ($iterator as $record) {
            if ($isNext) {
                return $record;
            }

            if ($id == $record[$this->getIdentifier()]) {
                $isNext = true;
            }
        }

        if ($isNext) {
            return $iterator->current();
        }

        throw new DataStoreException("Can't find record with id = {$id}");
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return static::DEF_ID;
    }

    /**
     * @param $id
     * @throws DataStoreException
     */
    protected function checkIdentifierType($id)
    {
        trigger_error("This method is deprecated. Use 'AspectTyped' to define any field type.", E_USER_DEPRECATED);

        $idType = gettype($id);

        if ($idType == 'integer' || $idType == 'double' || $idType == 'string') {
            return;
        } else {
            throw new DataStoreException("Type of Identifier is wrong - " . $idType);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function query(Query $query)
    {
        $limitNode = $query->getLimit();
        $limit = !$limitNode
            ? self::LIMIT_INFINITY
            : $query->getLimit()
                ->getLimit();
        $offset = !$limitNode
            ? 0
            : $query->getLimit()
                ->getOffset();

        $data = $this->queryWhere($query, self::LIMIT_INFINITY, 0);//Mus be disable
        $result = $this->querySort($data, $query);

        if ($query instanceof RqlQuery && $query->getGroupBy() != null) {
            $result = $this->queryGroupBy($result, $query);
        } else {
            $result = $this->querySelect($result, $query);
        }

        $result = array_slice($result, $offset, $limit == self::LIMIT_INFINITY ? null : $limit);

        // Filled item unset field
        $itemFiled = [];

        foreach ($result as &$item) {
            $keys = array_keys($item);
            $diff = array_diff($keys, $itemFiled);
            $itemFiled = array_merge($itemFiled, $diff);
            $diff = array_diff($itemFiled, $keys);

            foreach ($diff as $field) {
                $item[$field] = null;
            }
        }

        return $result;
    }

    /**
     * @param Query $query
     * @param $limit
     * @param $offset
     * @return array
     */
    protected function queryWhere(Query $query, $limit, $offset)
    {
        $conditionBuilder = $this->conditionBuilder;
        $condition = $conditionBuilder($query->getQuery());

        $whereFunctionBody = PHP_EOL . '$result = ' . PHP_EOL . rtrim($condition, PHP_EOL) . ';' . PHP_EOL
            . 'return $result;';

        $whereFunction = create_function('$item', $whereFunctionBody);
        $suitableItemsNumber = 0;
        $result = [];

        foreach ($this as $value) {
            switch (true) {
                case (!($whereFunction($value))):
                    break;
                case $suitableItemsNumber < $offset:
                    $suitableItemsNumber = $suitableItemsNumber + 1;
                    break;
                case $limit <> self::LIMIT_INFINITY && $suitableItemsNumber >= ($limit + $offset):
                    return $result;
                default:
                    $result[] = $value;
                    $suitableItemsNumber = $suitableItemsNumber + 1;
            }
        }

        return $result;
    }

    protected function querySort($data, Query $query)
    {
        if (empty($query->getSort())) {
            return $data;
        }

        $nextCompareLevel = '';
        $sortFields = $query->getSort()
            ->getFields();

        foreach ($sortFields as $ordKey => $ordVal) {
            if ((int)$ordVal <> SortNode::SORT_ASC && (int)$ordVal <> SortNode::SORT_DESC) {
                throw new DataStoreException('Invalid condition: ' . $ordVal);
            }

            $cond = $ordVal == SortNode::SORT_DESC ? '<' : '>';
            $notCond = $ordVal == SortNode::SORT_ASC ? '<' : '>';
            $ordKeySafe = "'" . addslashes($ordKey) . "'";

            $prevCompareLevel = "if (!isset(\$a[$ordKeySafe])) {return 0;};" . PHP_EOL
                . "if (\$a[$ordKeySafe] $cond \$b[$ordKeySafe]) {return 1;};" . PHP_EOL
                . "if (\$a[$ordKeySafe] $notCond  \$b[$ordKeySafe]) {return -1;};" . PHP_EOL;

            $nextCompareLevel = $nextCompareLevel . $prevCompareLevel;
        }

        $sortFunctionBody = $nextCompareLevel . 'return 0;';
        $sortFunction = create_function('$a,$b', $sortFunctionBody);
        usort($data, $sortFunction);

        return $data;
    }

    protected function queryGroupBy($result, RqlQuery $query)
    {
        $groupFields = $query->getGroupBy()
            ->getFields();
        $selectionFields = $query->getSelect()
            ->getFields();

        foreach ($selectionFields as &$field) {
            if (!in_array($field, $groupFields) && !($field instanceof AggregateFunctionNode)) {
                $field = new AggregateFunctionNode('count', $field);
            }
        }

        $query->setSelect(new AggregateSelectNode($selectionFields));

        $groups = [$result];
        $groups = $this->groupBy($groups, $groupFields);

        $result = [];

        foreach ($groups as $group) {
            $data = $this->querySelect($group, $query);
            $union = [];

            foreach ($data as $item) {
                $union = array_merge($union, $item);
            }

            $result = array_merge($result, [$union]);
        }

        return $result;
    }

    protected function groupBy(array $groups, $groupFields)
    {
        $newGroup = [];

        foreach ($groups as $group) {
            foreach ($group as $item) {
                $key = '';

                foreach ($groupFields as $groupField) {
                    $key .= $item[$groupField];
                }

                $newGroup[$key][] = $item;
            }
        }

        return $newGroup;
    }

    protected function querySelect($data, Query $query)
    {
        $selectNode = $query->getSelect();

        if (empty($selectNode)) {
            return $data;
        } else {
            $resultArray = [];
            $compareArray = [];

            foreach ($selectNode->getFields() as $fieldNode) {
                if ($fieldNode instanceof AggregateFunctionNode) {
                    switch ($fieldNode->getFunction()) {
                        case 'count':
                            $arr = [];

                            foreach ($data as $item) {
                                if (isset($item[$fieldNode->getField()])) {
                                    $arr[] = $item[$fieldNode->getField()];
                                }
                            }

                            $compareArray[$fieldNode->__toString()] = [count($arr)];
                            break;
                        case 'max':
                            $firstItem = array_pop($data);
                            $max = $firstItem[$fieldNode->getField()];

                            foreach ($data as $item) {
                                $max = $max < $item[$fieldNode->getField()] ? $item[$fieldNode->getField()] : $max;
                            }

                            array_push($data, $firstItem);
                            $compareArray[$fieldNode->__toString()] = [$max];
                            break;
                        case 'min':
                            $firstItem = array_pop($data);
                            $min = $firstItem[$fieldNode->getField()];

                            foreach ($data as $item) {
                                $min = $min > $item[$fieldNode->getField()] ? $item[$fieldNode->getField()] : $min;
                            }

                            array_push($data, $firstItem);
                            $compareArray[$fieldNode->__toString()] = [$min];
                            break;
                        case 'sum':
                            $sum = 0;

                            foreach ($data as $item) {
                                $sum += isset($item[$fieldNode->getField()]) ? $item[$fieldNode->getField()] : 0;
                            }

                            $compareArray[$fieldNode->__toString()] = [$sum];
                            break;
                        case 'avg':
                            $sum = 0;
                            $count = 0;

                            foreach ($data as $item) {
                                $sum += isset($item[$fieldNode->getField()]) ? $item[$fieldNode->getField()] : 0;
                                $count += isset($item[$fieldNode->getField()]) ? 1 : 0;
                            }

                            $compareArray[$fieldNode->__toString()] = [$sum / $count];
                            break;
                    }
                } else {
                    $dataLine = [];

                    foreach ($data as $item) {
                        $dataLine[] = $item[$fieldNode];
                    }

                    $compareArray[$fieldNode] = $dataLine;
                }
            }
            $min = null;

            foreach ($compareArray as $column) {
                if (!isset($min)) {
                    $min = count($column);
                } elseif (count($column) < $min) {
                    $min = count($column);
                }
            }

            for ($i = 0; $i < $min; ++$i) {
                $item = [];

                foreach ($compareArray as $fieldName => $column) {
                    $item[$fieldName] = $column[$i];
                }

                $resultArray[] = $item;
            }

            return $resultArray;
        }
    }

    /**
     * {@inheritdoc}
     */
    abstract public function create($itemData, $rewriteIfExist = false);

    /**
     * {@inheritdoc}
     *
     * @param array $record
     * @param array
     */
    public function multiCreate($records)
    {
        $ids = [];

        foreach ($records as $record) {
            try {
                $createdRecord = $this->create($record);
                $ids[] = $createdRecord[$this->getIdentifier()];
            } catch (\Throwable $e) {
                // TODO: need to log record that was not created
                continue;
            }
        }

        return $ids;
    }

    /**
     * {@inheritdoc}
     */
    abstract public function update($itemData, $createIfAbsent = false);

    /**
     * {@inheritdoc}
     *
     * @param array $record
     * @param array
     */
    public function multiUpdate($records)
    {
        $ids = [];

        foreach ($records as $record) {
            try {
                $updatedRecord = $this->update($record);
                $ids[] = $updatedRecord[$this->getIdentifier()];
            } catch (\Throwable $e) {
                // TODO: need to log record that was not updated
                continue;
            }
        }

        return $ids;
    }

    /**
     * {@inheritdoc}
     *
     * @param $record
     * @param Query $query
     * @return array
     */
    public function queriedUpdate($record, Query $query)
    {
        $identifier = $this->getIdentifier();

        if (isset($record[$identifier])) {
            throw new DataStoreException('Primary key is not allowed in record for queried update');
        }

        $forUpdateRecords = $this->query($query);
        $updatedIds = [];

        foreach ($forUpdateRecords as $forUpdateRecord) {
            try {
                $updatedRecord = $this->update(array_merge($record, [$identifier => $forUpdateRecord[$identifier]]));
                $updatedIds[] = $updatedRecord[$identifier];
            } catch (\Throwable $e) {
                // TODO: log failed queried updated record
                continue;
            }
        }

        return $updatedIds;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAll()
    {
        $keys = $this->getKeys();
        $deletedItemsNumber = 0;

        foreach ($keys as $id) {
            $deletedItems = $this->delete($id);

            if (is_null($deletedItems)) {
                return null;
            }

            $deletedItemsNumber++;
        }

        return $deletedItemsNumber;
    }

    /**
     * Return array of keys or empty array
     *
     * @return array
     */
    protected function getKeys()
    {
        $identifier = $this->getIdentifier();

        $query = new Query();
        $selectNode = new Node\SelectNode([$identifier]);
        $query->setSelect($selectNode);
        $queryResult = $this->query($query);
        $keysArray = [];

        foreach ($queryResult as $row) {
            $keysArray[] = $row[$identifier];
        }

        return $keysArray;
    }

    /**
     * {@inheritdoc}
     *
     * @param array $record
     * @param array
     */
    public function rewrite($record)
    {
        if (!isset($record[$this->getIdentifier()])) {
            throw new DataStoreException("Identifier is required for 'rewrite' action");
        }

        $rewriteIfExist = false;

        if ($this->has($record[$this->getIdentifier()])) {
            $rewriteIfExist = true;
        }

        return $this->create($record, $rewriteIfExist);
    }

    /**
     * {@inheritdoc}
     *
     * @param array[] $records
     * @return array
     */
    public function multiRewrite($records)
    {
        $ids = [];

        foreach ($records as $record) {
            if (!isset($record[$this->getIdentifier()])) {
                throw new DataStoreException("Identifier is required in 'multiRewrite' action for each record");
            }

            try {
                $rewroteRecord = $this->rewrite($record);
                $ids[] = $rewroteRecord[$this->getIdentifier()];
            } catch (\Throwable $e) {
                // TODO: need to log record that was not rewrote
                continue;
            }
        }

        return $ids;
    }

    /**
     * {@inheritdoc}
     */
    abstract public function delete($id);

    /**
     * {@inheritdoc}
     *
     * @param Query $query
     * @return array
     */
    public function queriedDelete(Query $query)
    {
        $identifier = $this->getIdentifier();
        $queryResult = $this->query($query);
        $deletedIds = [];

        foreach ($queryResult as $record) {
            try {
                $deletedRecord = $this->delete($record[$identifier]);
                $deletedIds[] = $deletedRecord[$identifier];
            } catch (\Throwable $e) {
                // TODO: need to log record that was not deleted
                continue;
            }
        }

        return $deletedIds;
    }

    /**
     * Interface 'Countable'
     *
     * @see Countable
     * @return int
     */
    public function count()
    {
        $keys = $this->getKeys();

        return count($keys);
    }

    /**
     * Iterator for Interface IteratorAggregate
     *
     * @see \IteratorAggregate
     * @return \Iterator
     */
    public function getIterator()
    {
        trigger_error("Datastore is no more iterable", E_USER_DEPRECATED);

        return new DataStoreIterator($this);
    }
}
