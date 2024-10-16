<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore;

use Exception;
use Iterator;
use rollun\datastore\DataSource\DataSourceInterface;
use rollun\datastore\DataStore\Iterators\CsvIterator;
use rollun\datastore\DataStore\ConditionBuilder\PhpConditionBuilder;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Xiag\Rql\Parser\Query;

/**
 * Class CsvBase
 * @package rollun\datastore\DataStore
 */
class CsvBase extends DataStoreAbstract implements DataSourceInterface
{
    protected const MAX_FILE_SIZE_FOR_CACHE = 8388608;
    protected const MAX_LOCK_TRIES = 30;
    protected const DEFAULT_DELIMITER = ';';

    protected string $csvDelimiter;
    /**
     * Column headings
     */
    protected array $columns;

    /**
     * @var resource
     */
    protected $fileHandler;

    protected LockInterface $writeLock;
    protected LockInterface $readLock;

    /**
     * Csv constructor. If file with this name doesn't exist attempts find it in document root directory
     *
     * @throws DataStoreException
     */
    public function __construct(
        protected string $filename,
        ?string $csvDelimiter,
        protected LockFactory $lockFactory
    )
    {
        // At first checks existing file as it is
        // If it doesn't exist converts to full name in the temporary folder
        if (!is_file($filename)) {
            $this->filename = realpath(
                sys_get_temp_dir() . DIRECTORY_SEPARATOR . trim($filename, DIRECTORY_SEPARATOR)
            );

            if (!is_file($this->filename)) {
                throw new DataStoreException('The specified source file does not exist');
            }
        }


        $this->csvDelimiter = $csvDelimiter !== null ? $csvDelimiter : self::DEFAULT_DELIMITER;

        $this->writeLock = $this->lockFactory->createLock('write:' . $this->filename);
        $this->readLock = $this->lockFactory->createLock('read:' . $this->filename);

        // Sets the column headings
        $this->getHeaders();

        $this->conditionBuilder = new PhpConditionBuilder();
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * {@inheritdoc}
     *
     * @throws DataStoreException
     */
    public function read($id = null)
    {
        $this->enableReadMode();
        $this->openFileForReading();
        $this->skipColumnHeaders();

        // In the CSV-format first row always containts the column headings
        // That's why first row is passed during the file opening
        // And then it reads the file until end of file won't found or won't found the indentifier
        $row = null;

        while (!feof($this->fileHandler)) {
            $row = $this->getTrueRow(
                fgetcsv($this->fileHandler, null, $this->csvDelimiter)
            );

            if ($row && $row[$this->getIdentifier()] == $id) {
                break;
            }
        }

        $this->releaseLocks();
        $this->closeFile();

        return $row;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): Iterator
    {
        trigger_error("Datastore is no more iterable", E_USER_DEPRECATED);

        return new CsvIterator($this);
    }

    /**
     * {@inheritdoc}
     */
    public function create($itemData, $rewriteIfExist = false)
    {
        if ($rewriteIfExist) {
            trigger_error("Option 'rewriteIfExist' is no more use", E_USER_DEPRECATED);
        }

        $this->enableWritingMode();

        $identifier = $this->getIdentifier();

        switch (true) {
            case (!isset($itemData[$identifier])):
                // There isn't item with identifier in the data set; creates a new item
                $item = $this->createNewItem($itemData);
                $item[$identifier] = $this->generatePrimaryKey();
                break;
            case (!$rewriteIfExist && !is_null($this->read($itemData[$identifier]))):
                throw new DataStoreException("Item is already exist with id = $itemData[$identifier]");
            default:
                // updates an existing item
                $id = $itemData[$identifier];
                $this->checkIdentifierType($id);
                $item = $this->createNewItem($itemData);
                break;
        }

        $this->flush($item);

        $this->releaseLocks();

        return $item;
    }

    /**
     * {@inheritdoc}
     */
    public function update($itemData, $createIfAbsent = false)
    {
        if ($createIfAbsent) {
            trigger_error("Option 'createIfAbsent' is no more use.", E_USER_DEPRECATED);
        }

        $identifier = $this->getIdentifier();

        if (!isset($itemData[$identifier])) {
            throw new DataStoreException('Item must have primary key');
        }

        $id = $itemData[$identifier];
        $this->checkIdentifierType($id);
        $item = $this->read($id);

        switch (true) {
            case (is_null($item) && !$createIfAbsent):
                throw new DataStoreException("Can't update item with id = $id: item does not exist.");
            case (is_null($item) && $createIfAbsent):
                // new item
                $item = $this->createNewItem($itemData);
                break;
        }

        foreach ($item as $key => $value) {
            if (isset($itemData[$key])) {
                $item[$key] = $itemData[$key];
            }
        }

        $this->flush($item);

        return $item;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        $this->enableWritingMode();

        $this->checkIdentifierType($id);
        // If item with specified id was found flushs file without it
        $item = $this->read($id);

        if (!is_null($item)) {
            $this->flush($item, true);

            return $item;
        }

        $this->releaseLocks();

        // Else do nothing
        return null;
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function deleteAll()
    {
        $this->enableWritingMode();

        // Count rows
        $count = $this->count();
        $tmpFile = tempnam("/tmp", uniqid() . '.tmp');
        $tempHandler = fopen($tmpFile, 'w');

        // Write the headings only and right away closes file
        fputcsv($tempHandler, $this->columns, $this->csvDelimiter);
        fclose($tempHandler);

        // Changes the original file to a temporary one.
        if (!rename($tmpFile, $this->filename)) {
            throw new DataStoreException("Failed to write the results to a file.");
        }

        $this->releaseLocks();

        return $count;
    }

    /**
     * Flushes all changes to temporary file which then will change the original one
     *
     * @param $item
     * @param bool $delete
     * @throws DataStoreException
     */
    protected function flush($item, bool $delete = false): void
    {
        // Create and open temporary file for writing
        $tmpFile = tempnam(sys_get_temp_dir(), uniqid() . '.tmp');
        $tempHandler = fopen($tmpFile, 'w');

        // Write headings
        fputcsv($tempHandler, $this->columns, $this->csvDelimiter);

        $identifier = $this->getIdentifier();
        $inserted = false;

        foreach ($this as $index => $row) {
            // Check an identifier; if equals and it doesn't need to delete - inserts new item
            if ($item[$identifier] == $row[$identifier]) {
                if (!$delete) {
                    $this->writeRow($tempHandler, $item);
                }
                // anyway marks row as inserted
                $inserted = true;
            } else {
                // Just it inserts row from source-file (copying)
                $this->writeRow($tempHandler, $row);
            }
        }

        // If the same item was not found and changed inserts the new item as the last row in the file
        if (!$inserted) {
            $this->writeRow($tempHandler, $item);
        }

        fclose($tempHandler);

        // Copies the temporary file to original.
        if (!copy($tmpFile, $this->filename)) {
            unlink($tmpFile);
            throw new DataStoreException("Failed to write the results to a file.");
        }

        unlink($tmpFile);
    }

    /**
     * Read mode allows any parallel process to read from file, but locks file for writing.
     *
     * @throws DataStoreException
     */
    public function enableReadMode(): void
    {
        try {
            $this->lockWithRetries($this->writeLock);
        } catch (DataStoreException $e) {
            throw new DataStoreException('Cannot lock file for writing: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Write mode locks file for both writing and reading.
     *
     * @throws DataStoreException
     */
    public function enableWritingMode(): void
    {
        try {
            $this->lockWithRetries($this->writeLock);
        } catch (DataStoreException $e) {
            throw new DataStoreException('Cannot lock file for writing: ' . $e->getMessage(), 0, $e);
        }

        try {
            $this->lockWithRetries($this->readLock);
        } catch (DataStoreException $e) {
            $this->writeLock->release();
            throw new DataStoreException('Cannot lock file for reading: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @throws DataStoreException
     */
    protected function openFileForReading(): void
    {
        try {
            $this->fileHandler = fopen($this->filename, 'r');
        } catch (Exception $e) {
            $this->releaseLocks();
            throw new DataStoreException(
                "Failed to open file. The specified file does not exist or one is closed for reading.",
                0,
                $e
            );
        }
    }

    /**
     * Locks the file
     *
     * @param int $nbTries - count of tries of locking queue
     * @throws DataStoreException
     */
    protected function lockWithRetries(LockInterface $lock, int $nbTries = 0): void
    {
        if ($lock->acquire()) {
            return;
        }

        if ($nbTries >= static::MAX_LOCK_TRIES) {
            throw new DataStoreException(
                sprintf("Reach max retry (%s) for locking queue file {$this->filename}", static::MAX_LOCK_TRIES)
            );
        }

        usleep(10);

        $this->lockWithRetries($lock, $nbTries + 1);
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function getAll()
    {
        if (filesize($this->filename) <= static::MAX_FILE_SIZE_FOR_CACHE) {
            $return = $this->query(new Query());
        } else {
            $return = $this->getIterator();
        }

        return $return;
    }

    /**
     * Closes file and release all locks
     */
    public function closeFile(): void
    {
        fclose($this->fileHandler);
    }

    protected function releaseLocks(): void
    {
        $this->readLock->release();
        $this->writeLock->release();
    }

    /**
     * Sets the column headings
     * @throws DataStoreException
     */
    public function getHeaders(): void
    {
        // Don't pass the first row!!
        $this->enableReadMode();
        $this->openFileForReading();
        $this->columns = fgetcsv($this->fileHandler, null, $this->csvDelimiter);
        $this->closeFile();
    }

    /**
     * Creates a new item, combines data with the column headings
     * @param $itemData
     * @return array
     */
    protected function createNewItem($itemData)
    {
        $item = array_flip($this->columns);

        foreach ($item as $key => $value) {
            if (isset($itemData[$key])) {
                $item[$key] = $itemData[$key];
            } else {
                $item[$key] = null;
            }
        }

        return $item;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        $count = 0;

        foreach ($this as $item) {
            $count++;
        }

        return $count;
    }

    /**
     * Returns the associative array with the column headings;
     * also checks and sanitize empty string and null value and converts type for the numeric fields
     * @param $row
     * @return array|null
     */
    public function getTrueRow($row)
    {
        if ($row) {
            array_walk(
                $row,
                function (&$item, $key) {
                    if ('' === $item) {
                        $item = null;
                    }

                    if ($item === '""') {
                        $item = '';
                    }

                    $isZeroFirstString = strlen($item) > 1 && substr($item, 0, 1) == "0";

                    if (is_numeric($item) && !$isZeroFirstString) {
                        if (intval($item) == $item) {
                            $item = intval($item);
                        } else {
                            $item = floatval($item);
                        }
                    }
                }
            );

            return array_combine($this->columns, $row);
        }

        return null;
    }

    /**
     * Writes the row in the csv-format
     * also converts empty string to string of two quotes
     * It's necessary to distinguish the difference between empty string and null value: both are writen as empty value
     * @param $fHandler
     * @param $row
     */
    public function writeRow($fHandler, $row)
    {
        array_walk(
            $row,
            function (&$item, $key) {
                switch (true) {
                    case ('' === $item):
                        $item = '""';
                        break;
                    case (true === $item):
                        $item = 1;
                        break;
                    case (false === $item):
                        $item = 0;
                        break;
                }
            }
        );
        fputcsv($fHandler, $row, $this->csvDelimiter);
    }

    /**
     * Generates an unique identifier
     * @return string
     */
    protected function generatePrimaryKey()
    {
        return uniqid();
    }

    /**
     * Returns the delimiter of csv fields
     * @return string
     */
    public function getCsvDelimiter()
    {
        return $this->csvDelimiter;
    }

    /**
     * The first row in csv-file contains the column headings, and these methods skips it
     */
    protected function skipColumnHeaders(): void
    {
        fgets($this->fileHandler);
    }
}
