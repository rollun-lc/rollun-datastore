<?php

declare(strict_types=1);

namespace rollun\datastore\DataStore\Aspect;

use Exception;
use rollun\datastore\DataStore\Interfaces\DataStoreInterface;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use Xiag\Rql\Parser\Query;

/**
 * Allow you to map record
 */
abstract class AspectEntityMapper implements DataStoreInterface, DataStoresInterface
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
     * Map original value to record
     */
    abstract protected function mapEntityToRecord($itemData);

    /**
     * Map record to original value
     */
    abstract protected function mapRecordToEntity($record);

    /**
     * Process the query before executing it
     */
    abstract protected function preProcessQuery(Query $query): Query;

    public function count(): int
    {
        return $this->dataStore->count();
    }

    public function create($itemData, $rewriteIfExist = false)
    {
        $record = $this->mapEntityToRecord($itemData);
        $result = $this->dataStore->create($record, $rewriteIfExist);
        return $this->mapRecordToEntity($result);
    }

    public function multiCreate($records)
    {
        $records = $this->mapEntitiesToRecords($records);
        $result = $this->dataStore->multiCreate($records);
        return $this->mapRecordsToEntities($result);
    }

    public function update($itemData, $createIfAbsent = false)
    {
        $record = $this->mapEntityToRecord($itemData);
        $result = $this->dataStore->update($record, $createIfAbsent);
        return $this->mapRecordToEntity($result);
    }

    public function multiUpdate($records)
    {
        $records = $this->mapEntitiesToRecords($records);
        $result = $this->dataStore->multiUpdate($records);
        return $this->mapRecordsToEntities($result);
    }

    public function queriedUpdate($record, Query $query)
    {
        $record = $this->mapEntityToRecord($record);
        $query = $this->preProcessQuery($query);
        $result = $this->dataStore->queriedUpdate($record, $query);
        return $this->mapRecordsToEntities($result);
    }

    public function rewrite($record)
    {
        $record = $this->mapEntityToRecord($record);
        $result = $this->dataStore->rewrite($record);
        return $this->mapRecordToEntity($result);
    }

    public function delete($id)
    {
        $result = $this->dataStore->delete($id);
        return $this->mapRecordToEntity($result);
    }

    public function queriedDelete(Query $query)
    {
        $query = $this->preProcessQuery($query);
        return $this->dataStore->queriedDelete($query);
    }

    public function getIdentifier()
    {
        return $this->dataStore->getIdentifier();
    }

    public function read($id)
    {
        $result = $this->dataStore->read($id);
        return $result === null ? null : $this->mapRecordToEntity($result);
    }

    public function has($id)
    {
        return $this->dataStore->has($id);
    }

    public function query(Query $query)
    {
        $query = $this->preProcessQuery($query);
        $result = $this->dataStore->query($query);
        return $this->mapRecordsToEntities($result);
    }

    public function deleteAll()
    {
        $query = $this->preProcessQuery(new Query());
        return $this->queriedDelete($query);
    }

    public function getIterator(): \Traversable
    {
        // This method is deprecated and we cannot use them
        throw new Exception('Not implemented.');
    }

    private function mapEntitiesToRecords(array $items): array
    {
        return array_map(fn($itemData) => $this->mapEntityToRecord($itemData), $items);
    }

    private function mapRecordsToEntities(array $records): array
    {
        return array_map(fn($record) => $this->mapRecordToEntity($record), $records);
    }
}
