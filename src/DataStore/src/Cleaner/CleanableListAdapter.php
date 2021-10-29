<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Cleaner;

use Exception;
use rollun\utils\Cleaner\CleanableList\CleanableListInterface;
use rollun\datastore\DataStore\Interfaces\DataStoreInterface;
use rollun\datastore\DataStoreCleanerException;

class CleanableListAdapter implements \IteratorAggregate, CleanableListInterface
{
    /**
     * @var DataStoreInterface
     */
    protected $datastore;

    public function __construct(DataStoreInterface $datastore)
    {
        $this->datastore = $datastore;
    }

    public function deleteItem($item)
    {
        $primaryKey = $this->datastore->getIdentifier();
        $id = $item[$primaryKey];

        try {
            $this->datastore->delete($id);
        } catch (Exception $exc) {
            throw new DataStoreCleanerException("Can't delete item with id = $id", 0, $exc);
        }
    }

    public function getIterator(): \Traversable
    {
        return $this->datastore;
    }
}
