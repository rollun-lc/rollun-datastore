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
use SplFileObject;
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

    protected ?SplFileObject $file = null;

    /**
     * Csv constructor. If file with this name doesn't exist attempts find it in document root directory
     *
     * @throws DataStoreException
     */
    public function __construct(
        protected string $filename,
        ?string $csvDelimiter
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

        // Sets the column headings
        $this->getHeaders();

        $this->conditionBuilder = new PhpConditionBuilder();
    }

    /**
     * Sets the column headings
     * @throws DataStoreException
     */
    public function getHeaders(): void
    {
        $this->enableReadMode();
        $this->columns = $this->file->fgetcsv($this->csvDelimiter);
        $this->releaseLocks();
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
    public function read($id = null): ?array
    {
        $this->enableReadMode();

        $row = $this->findInFile($id);

        $this->releaseLocks();

        return $row;
    }

    /**
     * {@inheritdoc}
     *
     *  feat(hvtWPJsD):
     *  Internal iterator for service passes without E_USER_DEPRECATED.
     *  Not intended for third-party client code.
     *
     *  We don't know exactly why E_USER_DEPRECATED was added to getIterator(),
     *  so we're collecting information this way.
     */
    public function getIterator(): Iterator
    {
        // trigger_error("Datastore is no more iterable", E_USER_DEPRECATED);

        return new CsvIterator($this);
    }

    /**
     * {@inheritdoc}
     * @throws DataStoreException
     */
    public function create($itemData, $rewriteIfExist = false)
    {
        if (!$this->wasCalledFrom(DataStoreAbstract::class, 'rewrite')
            && !$this->wasCalledFrom(DataStoreAbstract::class, 'rewriteMultiple')
            && $rewriteIfExist
        ) {
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
            case (!$rewriteIfExist && !is_null($this->findInFile($itemData[$identifier]))):
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
     * @throws DataStoreException
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

        $this->enableWritingMode();

        $id = $itemData[$identifier];
        $this->checkIdentifierType($id);
        $item = $this->findInFile($id);

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
     * @throws DataStoreException
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
     * @throws DataStoreException
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

        // After we rename file, SplFileObject still refers to old file, so we clear it
        unset($this->file);
        $this->file = null;

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

        foreach ($this->file as $index => $row) {
            // First row is headers.
            // If file has newline at the end than last line will be false (if no SplFileObject::READ_AHEAD flag).
            if ($index === 0 || $row === false) {
                continue;
            }

            $row = $this->getTrueRow($row);

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
            // SHARED LOCK aka reader lock - any number of processes MAY HAVE A SHARED LOCK simultaneously.
            $this->lockWithRetries(LOCK_SH);
        } catch (DataStoreException $e) {
            throw new DataStoreException('Cannot lock file for reading: ' . $e->getMessage(), 0, $e);
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
            // EXCLUSIVE LOCK. Only a single process may possess an exclusive lock to a given file at a time.
            $this->lockWithRetries(LOCK_EX);
        } catch (DataStoreException $e) {
            throw new DataStoreException('Cannot lock file for writing: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @throws DataStoreException
     */
    protected function getFile(): SplFileObject
    {
        if ($this->file !== null) {
            return $this->file;
        }

        try {
            $this->file = new SplFileObject($this->filename, 'r');
        } catch (Exception $e) {
            throw new DataStoreException(
                "Failed to open file. The specified file does not exist or one is closed for reading.",
                0,
                $e
            );
        }


        // A blank line in a CSV file will be returned as an array comprising a single null field unless using
        // SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE, in which case empty lines are skipped.
        $this->file->setFlags(SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE | SplFileObject::READ_CSV);
        $this->file->setCsvControl($this->csvDelimiter, escape: '');

        return $this->file;
    }

    /**
     * @param int $microsecondsBetweenRetries delay in microseconds between retries
     * @throws DataStoreException
     */
    protected function lockWithRetries(
        int $operation, int $maxTries = 40, int $microsecondsBetweenRetries = 50
    ): void
    {
        $file = $this->getFile();
        $tries = 0;

        while (!$file->flock($operation | LOCK_NB, $wouldBlock)) {
            if (!$wouldBlock) {
                throw new DataStoreException('Cannot lock file: EWOULDBLOCK errno condition.');
            }

            if ($tries++ > $maxTries) {
                throw new DataStoreException(sprintf(
                    "Reach max retry (%s) for locking queue file {$file->getFilename()}",
                    static::MAX_LOCK_TRIES
                ));
            }

            usleep($microsecondsBetweenRetries);
        }
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
     * @throws DataStoreException
     */
    protected function releaseLocks(): void
    {
        $result = $this->file?->flock(LOCK_UN);
        if ($result === false) {
            throw new DataStoreException("Cannot unblock file '{$this->filename}'.");
        }
    }

    protected function findInFile(?string $id): ?array
    {
        $this->file->rewind();
        $this->skipColumnHeaders($this->file);

        // In the CSV-format first row always containts the column headings
        // That's why first row is passed during the file opening
        // And then it reads the file until end of file won't found or won't found the indentifier
        $row = null;

        while (!$this->file->eof()) {
            $row = $this->file->fgetcsv($this->csvDelimiter);

            $row = $this->getTrueRow($row);

            if ($row && $row[$this->getIdentifier()] == $id) {
                break;
            }
        }

        return $row;
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
     * @throws DataStoreException
     */
    public function count(): int
    {
        $this->enableReadMode();

        // Not zero because first row is headers
        $count = -1;

        foreach ($this->file as $row) {
            // If file has newline at the end than last line will be false (if no SplFileObject::READ_AHEAD flag).
            if ($row === false) {
                continue;
            }
            $count++;
        }

        $this->releaseLocks();

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
    protected static function skipColumnHeaders(SplFileObject $file): void
    {
        $file->fgets();
    }
}
