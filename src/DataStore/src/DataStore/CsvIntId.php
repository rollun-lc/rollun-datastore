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
     * Auto-sort hook: insert the new item before the first row whose id is
     * greater. CsvBase::flush handles the rest of the rewrite/atomic replace
     * machinery.
     */
    protected function shouldInsertItemBefore(array $item, array $row, string $identifier, mixed $prevId): bool
    {
        return ($prevId === null || $item[$identifier] > $prevId)
            && $item[$identifier] < $row[$identifier];
    }

    /**
     * Returns last_data_row_id + 1, or 1 on a header-only / empty file.
     *
     * @return int
     * @throws DataStoreException
     */
    protected function generatePrimaryKey()
    {
        $this->getFile();
        $this->file->rewind();
        static::skipColumnHeaders($this->file);

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
