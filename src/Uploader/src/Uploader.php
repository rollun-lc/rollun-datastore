<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\uploader;

use rollun\datastore\DataStore\Interfaces\DataStoreInterface;
use SeekableIterator;
use Traversable;

/**
 * Class Uploader
 * @package rollun\uploader
 */
class Uploader
{
    /**
     * @var Traversable
     */
    protected $sourceDataIteratorAggregator;

    /**
     * @var DataStoreInterface
     */
    protected $destinationDataStore;

    /**
     * @var mixed Iterator position
     */
    protected $key = null;

    /**
     * Uploader constructor.
     * @param Traversable $sourceDataIteratorAggregator
     * @param DataStoreInterface $destinationDataStore
     */
    public function __construct(
        Traversable $sourceDataIteratorAggregator,
        DataStoreInterface $destinationDataStore
    ) {
        $this->sourceDataIteratorAggregator = $sourceDataIteratorAggregator;
        $this->destinationDataStore = $destinationDataStore;
    }

    public function upload()
    {
        if ($this->sourceDataIteratorAggregator instanceof SeekableIterator && isset($this->key)) {
            $this->sourceDataIteratorAggregator->seek($this->key);
        }

        foreach ($this->sourceDataIteratorAggregator as $key => $value) {
            $this->key = $key;
            $this->destinationDataStore->rewrite($value);
        }
    }

    /**
     * @param null $v
     */
    public function __invoke($v = null)
    {
        $this->upload();
    }

    public function __wakeup()
    {
        $this->key = null;
    }
}
