<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Iterators;

use rollun\datastore\DataStore\CsvBase;
use rollun\datastore\DataStore\DataStoreException;

class CsvIterator implements \Iterator
{
    /**
     * File handler.
     * @var resource
     */
    protected $fileHandler;

    /**
     * @var CsvBase
     */
    protected $dataStore;

    /**
     * @var \SplFileObject
     */
    protected $splFileObject;

    public function __construct(CsvBase $dataStore)
    {
        $filename = $dataStore->getFilename();

        if (!is_file($filename)) {
            throw new DataStoreException(sprintf('The specified file path "%s" does not exist', $filename));
        }

        $this->splFileObject = new \SplFileObject($filename);
        $this->splFileObject->setFlags(\SplFileObject::READ_CSV);
        $this->splFileObject->setCsvControl($dataStore->getCsvDelimiter());

        $this->dataStore = $dataStore;

        $this->lock();
    }

    /**
     * During destruction an object it unlocks the file and then closes one.
     */
    public function __destruct()
    {
        $this->unlock();
    }

    /**
     * @param int $maxTries
     * @param int $timeout
     * @throws DataStoreException
     */
    public function lock($maxTries = 40, $timeout = 50)
    {
        $count = 0;

        while (!$this->splFileObject->flock(LOCK_SH | LOCK_NB, $wouldblock)) {
            if (!$wouldblock) {
                throw new DataStoreException('There is a problem with file: ' . $this->splFileObject->getFilename());
            }

            if ($count++ > $maxTries) {
                throw new DataStoreException('Can not lock the file: ' . $this->splFileObject->getFilename());
            }

            usleep($timeout);
        }
    }

    /**
     * @return bool
     */
    public function unlock()
    {
        return $this->splFileObject->flock(LOCK_UN);
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->splFileObject->rewind();
        $this->splFileObject->current();
        $this->splFileObject->next();
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->splFileObject->key();
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        if ($this->splFileObject->key() === 0) {
            $this->rewind();
        }

        $this->splFileObject->next();
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        if ($this->splFileObject->key() === 0) {
            $this->rewind();
        }

        $row = $this->splFileObject->current();

        if ([null] === $row) {
            return null;
        }

        $item = $this->dataStore->getTrueRow($row);

        return $item;
    }

    /**
     * It checks if index is valid.
     * If index doesn't set it returns false.
     * Else reads first symbol after the file pointer. If this symbol is EOF it returns false.
     * Finally it sets the file pointer one byte back and returns true.
     * {@inheritdoc}
     */
    public function valid()
    {
        if (!$this->splFileObject->valid()) {
            return false;
        }

        if ($this->splFileObject->key() === 0) {
            $this->splFileObject->next();
        }

        $current = $this->splFileObject->current();

        return $current <> [null];
    }
}
