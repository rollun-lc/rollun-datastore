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
    protected const MAX_LOCK_TRIES = 40;
    protected const DEFAULT_DELIMITER = ';';

    /**
     * UTF-8 byte order mark. Excel-Win and several other CSV producers add
     * this to the start of the file. Without stripping, the first column
     * header becomes "\xEF\xBB\xBFid" instead of "id" and breaks every
     * subsequent column lookup.
     */
    private const UTF8_BOM = "\xEF\xBB\xBF";

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

        // findInFile stays under the LOCK_EX obtained by enableWritingMode().
        // Using the public read() here would call enableReadMode() followed by
        // releaseLocks(), silently dropping the exclusive lock before flush()
        // runs — a concurrent writer could then interleave.
        $item = $this->findInFile($id);

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

        // Count rows UNDER the existing LOCK_EX. Using the public count()
        // here would call enableReadMode() → releaseLocks() and silently
        // drop the exclusive lock before we overwrite the file.
        $count = $this->countRowsUnderLock();

        // Build the replacement file as a sibling so rename(2) stays on the
        // same filesystem and is POSIX-atomic. /tmp may be a different fs.
        $tmpFile = tempnam(dirname($this->filename), 'csv_');
        if ($tmpFile === false) {
            throw new DataStoreException(
                'Failed to create temp file for deleteAll in ' . dirname($this->filename),
            );
        }

        $tempHandler = fopen($tmpFile, 'w');
        if ($tempHandler === false) {
            @unlink($tmpFile);
            throw new DataStoreException("Failed to open temp file for writing: $tmpFile");
        }

        try {
            // Write the headings only — escape: '' = RFC 4180 mode.
            $this->fputcsvChecked($tempHandler, $this->columns);
            fclose($tempHandler);
            $tempHandler = null;

            // Preserve original file mode across the atomic replace.
            $originalMode = @fileperms($this->filename);
            if ($originalMode !== false) {
                @chmod($tmpFile, $originalMode & 0777);
            }

            if (!rename($tmpFile, $this->filename)) {
                throw new DataStoreException("Failed to atomically replace {$this->filename}");
            }
            $tmpFile = null;
        } finally {
            if ($tempHandler !== null) {
                @fclose($tempHandler);
            }
            if ($tmpFile !== null && is_file($tmpFile)) {
                @unlink($tmpFile);
            }
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
        if ($tmpFile === false) {
            throw new DataStoreException(
                'Failed to create temp file for flush in ' . dirname($this->filename),
            );
        }

        $tempHandler = fopen($tmpFile, 'w');
        if ($tempHandler === false) {
            @unlink($tmpFile);
            throw new DataStoreException("Failed to open temp file for writing: $tmpFile");
        }

        $identifier = $this->getIdentifier();
        $inserted = false;
        $prevId = null;

        try {
            // Write headings — escape: '' = RFC 4180 mode
            $this->fputcsvChecked($tempHandler, $this->columns);

            foreach ($this->file as $index => $row) {
                // First row is headers; false = trailing-newline artifact;
                // [null] = empty line yielded by READ_CSV without DROP_NEW_LINE.
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
                    $inserted = true;
                } elseif (
                    !$inserted
                    && !$delete
                    && $this->shouldInsertItemBefore($item, $row, $identifier, $prevId)
                ) {
                    // Subclass-controlled in-order insertion (CsvIntId uses
                    // this to keep the file sorted by integer id).
                    $this->writeRow($tempHandler, $item);
                    $this->writeRow($tempHandler, $row);
                    $inserted = true;
                } else {
                    $this->writeRow($tempHandler, $row);
                }

                $prevId = $row[$identifier];
            }

            if (!$inserted && !$delete) {
                $this->writeRow($tempHandler, $item);
            }

            fclose($tempHandler);
            $tempHandler = null;

            // Preserve original file mode across the atomic replace.
            $originalMode = @fileperms($this->filename);
            if ($originalMode !== false) {
                @chmod($tmpFile, $originalMode & 0777);
            }

            // Atomic replace via rename(2). Same-filesystem guaranteed by
            // tempnam in dirname() above.
            if (!rename($tmpFile, $this->filename)) {
                throw new DataStoreException("Failed to atomically replace {$this->filename}");
            }
            $tmpFile = null;
        } finally {
            // On any error path: close the handle, unlink the stray tmp so
            // we don't leak csv_* files in the data directory on disk-full,
            // malformed-row, or lock-failure scenarios.
            if ($tempHandler !== null) {
                @fclose($tempHandler);
            }
            if ($tmpFile !== null && is_file($tmpFile)) {
                @unlink($tmpFile);
            }
        }

        // After successful rename, the open SplFileObject still refers to the
        // orphaned old inode. Drop it so subsequent operations re-open.
        unset($this->file);
        $this->file = null;
    }

    /**
     * fputcsv() returns false on I/O failure (disk full, broken stream).
     * We route every write through this helper so a truncated tempfile can
     * never be rename()'d over the live data.
     */
    private function fputcsvChecked($handle, array $row): void
    {
        $result = fputcsv($handle, $row, $this->csvDelimiter, '"', '');
        if ($result === false) {
            throw new DataStoreException(
                'CSV write failed (disk full or unwritable stream)',
            );
        }
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
        ?int $maxTries = null,
        int $microsecondsBetweenRetries = 50
    ): void {
        $maxTries ??= static::MAX_LOCK_TRIES;
        $file = $this->getFile();
        $tries = 0;

        while (!$file->flock($operation | LOCK_NB, $wouldBlock)) {
            if (!$wouldBlock) {
                throw new DataStoreException('Cannot lock file: EWOULDBLOCK errno condition.');
            }

            if ($tries++ > $maxTries) {
                throw new DataStoreException(sprintf(
                    'Reach max retry (%d) for locking file %s',
                    $maxTries,
                    $file->getFilename(),
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

        // First row is column headers (already consumed above). Iterate data
        // rows until we find one whose identifier matches $id; otherwise the
        // function returns null.
        while (!$this->file->eof()) {
            $row = $this->getTrueRow($this->file->fgetcsv($this->csvDelimiter));

            if ($row === null) {
                continue;
            }

            if ($row[$this->getIdentifier()] == $id) {
                return $row;
            }
        }

        return null;
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
        try {
            return $this->countRowsUnderLock();
        } finally {
            $this->releaseLocks();
        }
    }

    /**
     * Count data rows assuming a lock is ALREADY held on $this->file.
     *
     * Callers that need the count as part of a larger locked operation
     * (e.g. deleteAll inside enableWritingMode) must use this instead of
     * count(), because count() calls enableReadMode + releaseLocks, which
     * would silently drop the exclusive lock mid-operation.
     */
    protected function countRowsUnderLock(): int
    {
        // Start at -1 because the first iterated row is the header.
        // Clamped at the end so a 0-byte file (no header, no data) reports 0.
        $count = -1;
        foreach ($this->file as $row) {
            if ($row === false || $row === [null]) {
                continue;
            }
            $count++;
        }
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
        // on length mismatch — convert to a typed DataStoreException. The
        // message deliberately omits the row payload: ragged rows often
        // contain PII (emails, tokens, addresses) and this exception string
        // flows into logs, Sentry, and stack traces.
        $expectedCount = count($this->columns);
        $actualCount = count($row);
        if ($actualCount !== $expectedCount) {
            throw new DataStoreException(sprintf(
                'Malformed CSV row: got %d fields, expected %d columns',
                $actualCount,
                $expectedCount,
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
        // Symmetric with the read path (setCsvControl escape: '').
        $this->fputcsvChecked($fHandler, $row);
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
