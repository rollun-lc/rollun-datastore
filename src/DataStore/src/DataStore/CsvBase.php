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
    ) {
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


        $this->csvDelimiter = $csvDelimiter ?? self::DEFAULT_DELIMITER;

        // Sets the column headings
        $this->getHeaders();

        $this->conditionBuilder = new PhpConditionBuilder();
    }

    /**
     * UTF-8 byte order mark. Excel-Win and several other CSV producers add
     * this to the start of the file. Without stripping, the first column
     * header becomes "\xEF\xBB\xBFid" instead of "id" and breaks every
     * subsequent column lookup.
     */
    private const UTF8_BOM = "\xEF\xBB\xBF";

    /**
     * Sets the column headings
     * @throws DataStoreException
     */
    public function getHeaders(): void
    {
        $this->enableReadMode();
        $this->file->rewind();
        $headers = $this->file->fgetcsv($this->csvDelimiter);

        if ($headers === false || $headers === [null]) {
            $this->columns = [];
        } else {
            // Strip leading UTF-8 BOM from the first column header if present.
            if (isset($headers[0])
                && is_string($headers[0])
                && str_starts_with($headers[0], self::UTF8_BOM)
            ) {
                $headers[0] = substr($headers[0], strlen(self::UTF8_BOM));
            }
            $this->columns = $headers;
        }

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

        // Build the replacement file as a sibling so rename(2) stays on the
        // same filesystem and is POSIX-atomic. /tmp may be a different fs.
        $tmpFile = tempnam(dirname($this->filename), 'csv_');
        $tempHandler = fopen($tmpFile, 'w');

        // Write the headings only and right away closes file
        // escape: '' = RFC 4180 mode ("" doubling, no \" backslash escape)
        fputcsv($tempHandler, $this->columns, $this->csvDelimiter, '"', '');
        fclose($tempHandler);

        // Preserve original file mode across the atomic replace.
        $originalMode = @fileperms($this->filename);
        if ($originalMode !== false) {
            @chmod($tmpFile, $originalMode & 0777);
        }

        // Atomic replace.
        if (!rename($tmpFile, $this->filename)) {
            @unlink($tmpFile);
            throw new DataStoreException("Failed to atomically replace {$this->filename}");
        }

        $this->releaseLocks();

        // After rename, SplFileObject still refers to the orphaned old inode.
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
        // Build the replacement file as a sibling of the destination so the
        // final rename(2) stays on the same filesystem and is POSIX-atomic.
        // Cross-fs renames degrade to copy+delete and lose atomicity.
        $tmpFile = tempnam(dirname($this->filename), 'csv_');
        $tempHandler = fopen($tmpFile, 'w');

        // Write headings — escape: '' = RFC 4180 mode
        fputcsv($tempHandler, $this->columns, $this->csvDelimiter, '"', '');

        $identifier = $this->getIdentifier();
        $inserted = false;
        $prevId = null; // tracked for shouldInsertItemBefore() hook

        foreach ($this->file as $index => $row) {
            // First row is headers.
            // If file has newline at the end than last line will be false (if no SplFileObject::READ_AHEAD flag).
            if ($index === 0 || $row === false || $row === [null]) {
                continue;
            }

            $row = $this->getTrueRow($row);
            if ($row === null) {
                continue;
            }

            // Check an identifier; if equals and it doesn't need to delete - inserts new item
            if ($item[$identifier] == $row[$identifier]) {
                if (!$delete) {
                    $this->writeRow($tempHandler, $item);
                }
                // anyway marks row as inserted
                $inserted = true;
            } elseif (
                !$inserted
                && !$delete
                && $this->shouldInsertItemBefore($item, $row, $identifier, $prevId)
            ) {
                // Subclass-controlled in-order insertion (CsvIntId uses this
                // to keep the file sorted by integer id).
                $this->writeRow($tempHandler, $item);
                $this->writeRow($tempHandler, $row);
                $inserted = true;
            } else {
                // Just it inserts row from source-file (copying)
                $this->writeRow($tempHandler, $row);
            }

            $prevId = $row[$identifier];
        }

        // If the same item was not found and changed inserts the new item as the last row in the file
        if (!$inserted && !$delete) {
            $this->writeRow($tempHandler, $item);
        }

        fclose($tempHandler);

        // Preserve original file mode across the atomic replace.
        $originalMode = @fileperms($this->filename);
        if ($originalMode !== false) {
            @chmod($tmpFile, $originalMode & 0777);
        }

        // Atomic replace via rename(2). Same-filesystem guaranteed by tempnam
        // in dirname() above.
        if (!rename($tmpFile, $this->filename)) {
            @unlink($tmpFile);
            throw new DataStoreException("Failed to atomically replace {$this->filename}");
        }

        // After rename, the open SplFileObject still refers to the orphaned
        // old inode. Drop it so subsequent operations re-open the new file.
        unset($this->file);
        $this->file = null;
    }

    /**
     * Hook for subclasses to control in-order insertion during flush().
     *
     * Called once per source-file row that does NOT match the item's id and
     * has not yet been inserted. Return true to write the item BEFORE the
     * current row (and continue copying the rest of the file).
     *
     * Default: false (no in-order insertion; new items are appended).
     * CsvIntId overrides this to maintain ascending-id order.
     *
     * @param array       $item       the item being created/updated
     * @param array       $row        the current source-file row
     * @param string      $identifier the primary key column name
     * @param mixed|null  $prevId     id of the previously written row, or null at file start
     */
    protected function shouldInsertItemBefore(array $item, array $row, string $identifier, mixed $prevId): bool
    {
        return false;
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


        // READ_CSV alone — DROP_NEW_LINE was previously included, but it
        // also strips embedded \n / \r\n inside quoted fields, corrupting
        // multi-line values (e.g. "line1\nline2" was being read as
        // "line1line2"). SKIP_EMPTY only works in combination with
        // READ_AHEAD which we do not set, so it's effectively a no-op here;
        // we filter [null] rows defensively in the iteration paths instead.
        $this->file->setFlags(SplFileObject::READ_CSV);
        $this->file->setCsvControl($this->csvDelimiter, escape: '');

        return $this->file;
    }

    /**
     * @param int $microsecondsBetweenRetries delay in microseconds between retries
     * @throws DataStoreException
     */
    protected function lockWithRetries(
        int $operation,
        int $maxTries = 40,
        int $microsecondsBetweenRetries = 50
    ): void {
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
        static::skipColumnHeaders($this->file);

        // In the CSV-format first row always containts the column headings
        // That's why first row is passed during the file opening
        // And then it reads the file until end of file won't found or won't found the indentifier
        $row = null;

        while (!$this->file->eof()) {
            $row = $this->file->fgetcsv($this->csvDelimiter);

            $row = $this->getTrueRow($row);

            // Skip blank/spurious rows (getTrueRow returns null for [null]
            // and false-like inputs); only stop on a matching id.
            if ($row === null) {
                continue;
            }

            if ($row[$this->getIdentifier()] == $id) {
                break;
            }

            // Reset so the function returns null if we exit the loop without
            // a match (otherwise the last non-matching row would leak out).
            $row = null;
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
        if ($this->columns === []) {
            // Allow creating first row in an empty csv file by deriving columns from payload.
            $this->columns = array_values(
                array_unique(
                    array_merge([$this->getIdentifier()], array_keys($itemData))
                )
            );
        }

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

        // Start at -1 because the first iterated row is the header.
        // Clamped at the end so a 0-byte file (no header, no data) reports 0
        // instead of -1.
        $count = -1;

        foreach ($this->file as $row) {
            // If file has newline at the end than last line will be false (if no SplFileObject::READ_AHEAD flag).
            // [null] is the SplFileObject representation of an empty line.
            if ($row === false || $row === [null]) {
                continue;
            }
            $count++;
        }

        $this->releaseLocks();

        return max(0, $count);
    }

    /**
     * Returns the associative array with the column headings;
     * also checks and sanitize empty string and null value and converts type for the numeric fields
     * @param $row
     * @return array|null
     */
    public function getTrueRow($row)
    {
        // [null] is what SplFileObject::READ_CSV emits for an empty line
        // (since we no longer set DROP_NEW_LINE). Treat it as no-row.
        if (!$row || $row === [null]) {
            return null;
        }

        // Ragged-row guard. array_combine() throws an untyped PHP ValueError
        // on length mismatch — convert to a typed DataStoreException with
        // enough context to debug the malformed file.
        $expectedCount = count($this->columns);
        $actualCount = count($row);
        if ($actualCount !== $expectedCount) {
            throw new DataStoreException(sprintf(
                "Malformed CSV row in '%s': %d fields, expected %d columns. Row: %s",
                $this->filename,
                $actualCount,
                $expectedCount,
                json_encode($row, JSON_UNESCAPED_UNICODE),
            ));
        }

        array_walk(
            $row,
            function (&$item, $key): void {
                if ('' === $item) {
                    $item = null;
                }

                if ($item === '""') {
                    $item = '';
                }

                // Null short-circuits the rest of the coercion. strlen()
                // and str_starts_with() emit PHP 8.1+ deprecations on null.
                if ($item === null) {
                    return;
                }

                $isZeroFirstString = strlen($item) > 1 && str_starts_with($item, "0");

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
            function (&$item, $key): void {
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
        // escape: '' = RFC 4180 mode ("" doubling, no \" backslash escape).
        // Symmetric with the read path, which uses setCsvControl(escape: '').
        fputcsv($fHandler, $row, $this->csvDelimiter, '"', '');
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
