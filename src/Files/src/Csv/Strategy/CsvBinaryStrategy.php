<?php
declare(strict_types=1);

namespace rollun\files\Csv\Strategy;

use rollun\files\Csv\CsvFileObjectWithPrKey;
use rollun\files\FileObject as RollunFileObject;
use InvalidArgumentException;

/**
 * Class CsvBinaryStrategy
 *
 * File specifications:
 *  1) File should always be sorted by ID (ASC)
 *
 * @author  Roman Ratsun <r.ratsun.rollun@gmail.com>
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */
class CsvBinaryStrategy implements CsvStrategyInterface
{
    /**
     * Max count of iterations
     */
    const ITERATION_MAX = 30;

    /**
     * @var CsvFileObjectWithPrKey
     */
    protected $fileObjectWithPrKey;

    /**
     * @var array
     */
    protected $uniqueIterations = [];

    /**
     * @var int
     */
    protected $idColumn;

    /**
     * @inheritDoc
     */
    public function __construct(CsvFileObjectWithPrKey $fileObjectWithPrKey, int $idColumn)
    {
        $this->fileObjectWithPrKey = $fileObjectWithPrKey;
        $this->idColumn = $idColumn;
    }

    /**
     * @inheritDoc
     */
    public function getRowById(string $id): ?array
    {
        $this->getFileObject()->lock(LOCK_EX);
        $result = $this->search($id);
        $this->getFileObject()->unlock();

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function addRow(array $row): int
    {
        $this->getFileObject()->lock(LOCK_EX);

        // find file row
        $fileRow = $this->search($this->getId($row));

        if ($fileRow !== null) {
            $this->getFileObject()->unlock();
            throw new InvalidArgumentException("Row with such ID already exists");
        }

        // parse row
        $parsedRow = $this->parseRow($row);
        $parsedRowLength = strlen($parsedRow);

        $current = $this->getFileObject()->current();
        if (is_bool($current) || $this->getId($current) === null) {
            $pos1 = $this->getFileObject()->getFileSize();
            if (!$this->isNewline($pos1)) {
                $parsedRow = "\r\n" . $parsedRow;
            } else {
                $parsedRow .= "\r\n";
            }
        } elseif ($this->getId($current) > $this->getId($row)) {
            $pos1 = $this->preparePosition($this->getFileObject()->ftell() - strlen($this->parseRow($current)));
            $parsedRow .= "\r\n";
        } else {
            $pos1 = $this->getFileObject()->ftell();
            $parsedRow = "\r\n" . $parsedRow;
        }

        $this->getFileObject()->moveSubStr($pos1, $pos1 + $parsedRowLength + 2);
        $this->getFileObject()->fseek($pos1);
        $this->getFileObject()->fwriteWithCheck($parsedRow, $parsedRowLength + 2);

        $this->getFileObject()->unlock();

        return $parsedRowLength;
    }

    /**
     * @inheritDoc
     */
    public function setRow(array $row): int
    {
        $this->getFileObject()->lock(LOCK_EX);

        // find file row
        $fileRow = $this->search($this->getId($row));

        if ($fileRow === null) {
            $this->getFileObject()->unlock();
            throw new InvalidArgumentException("No row with such ID");
        }

        // parse row
        $parsedRow = $this->parseRow($row);
        $parsedRowLength = strlen($parsedRow);

        // find start position
        $start = $this->correctingPointer($this->preparePosition($this->getFileObject()->ftell()));

        // delete old row
        $this->getFileObject()->moveSubStr($start + strlen($this->parseRow($fileRow)), $start);

        // prepare space for new row
        $this->getFileObject()->moveSubStr($start, $start + $parsedRowLength);

        // move pointer to start position
        $this->getFileObject()->fseek($start);

        // set new row
        $this->getFileObject()->fwriteWithCheck($parsedRow, $parsedRowLength);

        $this->getFileObject()->unlock();

        return $parsedRowLength;
    }

    /**
     * @param string   $id
     * @param int|null $from
     * @param int|null $to
     *
     * @return array|null
     */
    protected function search(string $id, int $from = null, int $to = null): ?array
    {
        // prepare from
        if ($from === null) {
            $from = 0;
            $this->getFileObject()->rewind();
        }

        // prepare to
        if ($to === null) {
            $to = $this->getFileObject()->getFileSize();
        }

        // prepare unique iteration
        $uniqueIteration = "{$from}_{$to}";

        // exit if file is too big
        if (count($this->uniqueIterations) == self::ITERATION_MAX) {
            throw new InvalidArgumentException('Csv file is too big');
        }

        // exit if such iteration already exists
        if (in_array($uniqueIteration, $this->uniqueIterations)) {
            // try next row
            $this->getFileObject()->next();
            $nextRow = $this->getFileObject()->current();
            if (is_array($nextRow)) {
                $nextRowId = $this->getId($nextRow);
                if ($nextRowId !== null && $nextRowId == $id) {
                    return $nextRow;
                }
            }

            return null;
        }

        // push unique iteration
        $this->uniqueIterations[] = $uniqueIteration;

        // get middle position
        $middlePos = (int)floor(($to - $from) / 2 + $from);

        // correcting pointer position
        $pos = $this->correctingPointer($middlePos);

        $this->getFileObject()->ftell();
        $this->getFileObject()->key();

        $row = $this->getFileObject()->current();

        if (!is_array($row)) {
            return null;
        }

        $rowId = $this->getId($row);

        if ($rowId === null) {
            return null;
        }

        if ($rowId == $id) {
            return $row;
        }

        // find in left part
        if ($id < $rowId) {
            return $this->search($id, $from, $pos);
        }

        // find in right part
        if ($id > $rowId) {
            return $this->search($id, $pos, $to);
        }
    }

    /**
     * @return RollunFileObject
     */
    protected function getFileObject(): RollunFileObject
    {
        return $this->fileObjectWithPrKey->getFileObject();
    }

    /**
     * @param array $row
     *
     * @return string|null
     */
    protected function getId(array $row): ?string
    {
        if (!isset($row[$this->idColumn])) {
            return null;
        }

        return (string)$row[$this->idColumn];
    }

    /**
     * @param array $row
     *
     * @return string
     */
    private function parseRow(array $row): string
    {
        array_walk($row, function (&$v){
            $v = (string)$v . '\zaq1';
        });

        $tmp = tmpfile();
        fputcsv($tmp, $row, $this->fileObjectWithPrKey->getDelimiter(), $this->fileObjectWithPrKey->getEnclosure(), $this->fileObjectWithPrKey->getEscape());
        $length = (ftell($tmp)) - 1;
        rewind($tmp);
        $result = fread($tmp, $length);
        fclose($tmp);

        return str_replace('\zaq1"', '"', $result);
    }

    /**
     * @param int $pos
     *
     * @return bool
     */
    private function isNewline(int $pos): bool
    {
        $this->getFileObject()->fseek($this->getFileObject()->ftell() - 2);
        $str = '';
        $str .= $this->getFileObject()->fgetc();
        $str .= $this->getFileObject()->fgetc();

        return strpos($str, "\r\n") !== false;
    }

    /**
     * @param int $pos
     *
     * @return int
     */
    private function preparePosition(int $pos): int
    {
        if ($this->isNewline($pos)) {
            $pos = $pos - 2;
        }

        return $pos;
    }

    /**
     * @param int $pos
     */
    private function correctingPointer(int $pos): int
    {
        $str = '';
        while ($pos > 0) {
            $pos--;
            $this->getFileObject()->fseekWithCheck($pos);
            $str = $this->getFileObject()->fgetc() . $str;
            if (strpos($str, "\r\n") !== false) {
                $pos = $pos + 2;
                $this->getFileObject()->fseekWithCheck($pos);
                break;
            }
        }

        return $pos;
    }
}
