<?php

namespace rollun\datastore\DataStore\Aspect;

use rollun\datastore\DataStore\Interfaces\DataStoreInterface;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use rollun\datastore\Rql\Node\AggregateFunctionNode;
use rollun\datastore\Rql\Node\AggregateSelectNode;
use rollun\utils\Json\Exception;
use Xiag\Rql\Parser\Query;

/**
 * Allows you to modify all requests and responses of the datastore.
 *
 *
 * @package rollun\datastore\DataStore\Aspect
 */
abstract class AspectModifyTable implements DataStoreInterface, DataStoresInterface
{
    /**
     * @var DataStoresInterface
     */
    private $dataStore;

    public function __construct(DataStoresInterface $dataStore)
    {
        $this->dataStore = $dataStore;
    }

    /**
     * Process the record before it enters datastore
     *
     * There is no need to duplicate the identifier processing logic if
     * it is described in the preProcessId method.
     *
     * @param $record
     * @return mixed processed record
     */
    abstract protected function preProcessRecord($record);

    /**
     * Process the query before executing it
     *
     * Take note that 'select' node will be overwritten for count statement
     *
     * @param Query $query
     * @return Query processed query
     */
    abstract protected function preProcessQuery(Query $query): Query;

    /**
     * Process the identifier value before using it (for read or writing)
     *
     * @param $id
     * @return mixed processed id
     */
    abstract protected function preProcessId($id);

    /**
     * Processes the record after retrieving it from the datastore
     *
     * There is no need to duplicate the identifier processing logic if
     * it is described in the postProcessId method.
     *
     * @param $record
     * @return mixed processed record
     */
    abstract protected function postProcessRecord($record);

    /**
     * Process the identifier value after it retrieving it from the datastore
     *
     * @param $id
     * @return mixed processed id
     */
    abstract protected function postProcessId($id);

    // help methods

    private function _preProcessRecords(array $records)
    {
        return array_map(function (array $record) {
            return $this->_preProcessRecord($record);
        }, $records);
    }

    private function _preProcessRecord($record)
    {
        // Process identifier value
        if (isset($record[$this->getIdentifier()])) {
            $newId = $this->preProcessId($record[$this->getIdentifier()]);
            $record[$this->getIdentifier()] = $newId;
        }

        return $this->preProcessRecord($record);
    }

    private function _postProcessRecords($records)
    {
        $result = array_map(function ($record) {
            return $this->_postProcessRecord($record);
        }, $records);

        return $result;
    }

    private function _postProcessRecord($record)
    {
        if (!$record) {
            return $record;
        }

        // Process identifier value
        if (isset($record[$this->getIdentifier()])) {
            $newId = $this->postProcessId($record[$this->getIdentifier()]);
            $record[$this->getIdentifier()] = $newId;
        }

        return $this->postProcessRecord($record);
    }

    private function _postProcessIds($ids)
    {
        $result = array_map(function ($id) {
            return $this->postProcessId($id);
        }, $ids);

        return $result;
    }

    // DataStoreInterface methods

    public function getIterator(): \Traversable
    {
        // This method is deprecated and we cannot use them
        throw new Exception('Not implemented.');
    }

    public function count(): int
    {
        $select = new AggregateSelectNode([new AggregateFunctionNode('count', $this->getIdentifier())]);

        $rql = $this->preProcessQuery(new Query());
        $rql->setSelect($select);

        $result = $this->dataStore->query($rql);
        $firstRow = reset($result);

        return $firstRow['count(' . $this->getIdentifier() . ')'];
    }

    public function create($itemData, $rewriteIfExist = false)
    {
        $record = $this->_preProcessRecord($itemData);
        $result = $this->dataStore->create($record, $rewriteIfExist);
        return $this->_postProcessRecord($result);
    }

    public function multiCreate($records)
    {
        $records = $this->_preProcessRecords($records);
        $result = $this->dataStore->multiCreate($records);
        return $this->_postProcessRecords($result);
    }

    public function update($itemData, $createIfAbsent = false)
    {
        $record = $this->_preProcessRecord($itemData);
        $result = $this->dataStore->update($record, $createIfAbsent);
        return $this->_postProcessRecord($result);
    }

    public function multiUpdate($records)
    {
        $records = $this->_preProcessRecords($records);
        $result = $this->dataStore->multiUpdate($records);
        return $this->_postProcessRecords($result);
    }

    public function queriedUpdate($record, Query $query)
    {
        $record = $this->_preProcessRecord($record);
        $query = $this->preProcessQuery($query);
        $result = $this->dataStore->queriedUpdate($record, $query);
        return $this->_postProcessRecords($result);
    }

    public function rewrite($record)
    {
        $record = $this->_preProcessRecord($record);
        $result = $this->dataStore->rewrite($record);
        return $this->_postProcessRecord($result);
    }

    public function delete($id)
    {
        $id = $this->preProcessId($id);
        $result = $this->dataStore->delete($id);
        return $this->_postProcessRecord($result);
    }

    public function queriedDelete(Query $query)
    {
        $query = $this->preProcessQuery($query);
        $result = $this->dataStore->queriedDelete($query);
        return $this->_postProcessIds($result);
    }

    public function getIdentifier()
    {
        return $this->dataStore->getIdentifier();
    }

    public function read($id)
    {
        $id = $this->preProcessId($id);
        $result = $this->dataStore->read($id);
        return $this->_postProcessRecord($result);
    }

    public function has($id)
    {
        $id = $this->preProcessId($id);
        return $this->dataStore->has($id);
    }

    public function query(Query $query)
    {
        $query = $this->preProcessQuery($query);
        $result = $this->dataStore->query($query);
        return $this->_postProcessRecords($result);
    }

    public function deleteAll()
    {
        $query = $this->preProcessQuery(new Query());
        $this->queriedDelete($query);
    }
}