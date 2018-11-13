<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\uploader;

use IteratorAggregate;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;

/**
 * Class Uploader
 * @package rollun\uploader
 */
class Uploader
{
    /**
     * @var  IteratorAggregate
     */
    protected $sourceDataIteratorAggregator;

    /**
     * @var DataStoresInterface
     */
    protected $destinationDataStore;

    /**
     * @var mixed Iterator position
     */
    protected $key = null;

    /**
     * Uploader constructor.
     * @param IteratorAggregate $sourceDataIteratorAggregator
     * @param DataStoresInterface $destinationDataStore
     */
    public function __construct(
        IteratorAggregate $sourceDataIteratorAggregator,
        DataStoresInterface $destinationDataStore
    ) {
        $this->sourceDataIteratorAggregator = $sourceDataIteratorAggregator;
        $this->destinationDataStore = $destinationDataStore;
    }

    public function upload()
    {
        $iterator = $this->sourceDataIteratorAggregator->getIterator();

        if (isset($this->key) && $iterator instanceof SeekableIterator) {
            $iterator->seek($this->key); //set iterator to last position.
        }

        foreach ($iterator as $key => $value) {
            $this->key = $key;
            $this->destinationDataStore->create($value, true);
        }
    }

    /**
     * @param null $v
     */
    public function __invoke($v = null)
    {
        $this->upload();
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        return [
            "iteratorAggregate",
            "destinationDataStore",
            "key",
        ];
    }

    /**
     *
     */
    public function __wakeup()
    {
        $this->__construct($this->sourceDataIteratorAggregator, $this->destinationDataStore);
    }
}
