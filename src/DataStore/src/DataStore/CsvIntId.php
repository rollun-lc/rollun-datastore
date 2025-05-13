<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore;

/**
 * Class CsvIntId
 * @package rollun\datastore\DataStore
 */
class CsvIntId extends CsvBase
{
    /**
     * {@inheritdoc}
     */
    public function __construct($filename, $delimiter)
    {
        parent::__construct($filename, $delimiter);

        if (!$this->checkIntegrityData()) {
            throw new DataStoreException('The source file contains wrong data');
        }
    }

    /**
     * {@inheritdoc}
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
        $prevId = -1;

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
            } elseif ($item[$identifier] > $prevId && $item[$identifier] < $row[$identifier]) {
                // inserting with auto sorting

                if (!$delete) {
                    $this->writeRow($tempHandler, $item);
                }

                $this->writeRow($tempHandler, $row);
                $inserted = true;
            } else {
                $this->writeRow($tempHandler, $row);
            }

            $prevId = min($item[$identifier], $row[$identifier]);
        }

        // If the same item was not found and changed it inserts the new item as the last row in the file
        if (!$inserted) {
            $this->writeRow($tempHandler, $item);
        }

        fclose($tempHandler);

        // Copies the original file to a temporary one.
        if (!copy($tmpFile, $this->filename)) {
            unlink($tmpFile);
            throw new DataStoreException("Failed to write the results to a file.");
        }

        unlink($tmpFile);
    }

    /**
     * @return null|string
     * @throws DataStoreException
     */
    protected function generatePrimaryKey()
    {
        $this->getFile();
        $id = null;

        while (!$this->file->eof()) {
            $row = $this->getTrueRow($this->file->fgetcsv($this->csvDelimiter));

            if ($row) {
                $id = $row[$this->getIdentifier()];
            }
        }

        return ++$id;
    }

    /**
     * Checks integrity data
     * @return bool
     * @throws \rollun\datastore\DataStore\DataStoreException
     */
    public function checkIntegrityData()
    {
        $prevId = 0;
        $identifier = $this->getIdentifier();

        foreach ($this as $item) {
            $this->checkIdentifierType($item[$identifier]);

            if ($item[$identifier] < $prevId) {
                throw new DataStoreException("This storage type supports only a list ordered by id ASC");
            }

            $prevId = $item[$identifier];
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function checkIdentifierType($id)
    {
        $idType = gettype($id);

        if ($idType == 'integer') {
            return true;
        } else {
            throw new DataStoreException("This storage type supports integer primary keys only");
        }
    }
}
