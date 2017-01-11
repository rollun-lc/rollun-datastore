<?php

namespace rollun\datastore\DataStore\Iterators;

use Symfony\Component\Filesystem\LockHandler;
use rollun\datastore\DataStore\CsvBase;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\Interfaces\ReadInterface;

class CsvIterator extends DataStoreIterator
{
    /**
     * File handler.
     * @var resource
     */
    protected $fileHandler;

    /** @var LockHandler $lockHandler */
    protected $lockHandler;

    /**
     * CsvIterator constructor.
     *
     * After the creation an object it opens the file (by filename) and locks one.
     *
     * @param ReadInterface $dataStore
     * @param $filename
     * @param LockHandler $lockHandler
     * @throws DataStoreException
     */
    public function __construct(ReadInterface $dataStore, $filename, LockHandler $lockHandler)
    {
        parent::__construct($dataStore);
        if (!is_file($filename)) {
            throw new DataStoreException(sprintf('The specified file path "%s" does not exist', $filename));
        }
        $this->lockHandler = $lockHandler;
        $this->lockFile($filename);
        $this->fileHandler = fopen($filename, 'r');
        // We always pass the first row because it contains the column headings.
        fgets($this->fileHandler);
    }

    /**
     * During destruction an object it unlocks the file and then closes one.
     */
    function __destruct()
    {
        fclose($this->fileHandler);
        $this->lockHandler->release();
    }

    protected function lockFile($filename, $nbTries = 0)
    {
        if (!$this->lockHandler->lock()) {
            if ($nbTries >= CsvBase::MAX_LOCK_TRIES) {
                throw new DataStoreException('Reach max retry for locking queue file ' . $filename);
            }
            usleep(10);
            return $this->lockFile($filename, ($nbTries + 1));
        }
    }


    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->index = 1;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        ++$this->index;
    }

    /**
     * It reads current row from the self file handler but uses a method from dataStore object for data conversion.
     * @return mixed
     */
    public function current()
    {
        return $this->dataStore->getTrueRow(
            fgetcsv($this->fileHandler, null, $this->dataStore->getCsvDelimiter())
        );
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
        if (!isset($this->index)) {
            return false;
        }
        $ch = fgetc($this->fileHandler);
        if (feof($this->fileHandler)) {
            return false;
        }
        fseek($this->fileHandler, -1, SEEK_CUR);
        return true;
    }

}