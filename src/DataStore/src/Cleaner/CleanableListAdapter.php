<?php

namespace rollun\datastore\Cleaner;

use Exception;
use rollun\utils\Cleaner\CleanableList\CleanableListInterface;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use rollun\datastore\DataStoreCleanerException;

class CleanableListAdapter implements \IteratorAggregate, CleanableListInterface
{

    /**
     * @var DataStoresInterface
     */
    protected $datastore;

    public function __construct(DataStoresInterface $datastore)
    {
        $this->datastore = $datastore;
    }

    /**
     * @param $item
     */
    public function deleteItem($item)
    {
        $primaryKey = $this->datastore->getIdentifier();
        $id = $item[$primaryKey];
        try {
            $this->datastore->delete($id);
        } catch (Exception $exc) {
            throw new DataStoreCleanerException(
            'Can\'t delete item with id=' . $id
            , DataStoreCleanerException::LOG_LEVEL_DEFAULT
            , $exc);
        }
    }

    public function getIterator(): \Traversable
    {
        return $this->datastore;
    }

}
