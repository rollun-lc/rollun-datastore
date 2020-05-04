<?php
declare(strict_types=1);

namespace rollun\files\Csv;

use rollun\files\FileObject as BaseFileObject;
use rollun\utils\Json\Exception as RollunException;
use rollun\utils\Json\Serializer;
use InvalidArgumentException;
use SplFileObject;

/**
 * Class CsvFileObject
 *
 * File specifications:
 *  1) '\n' - newline characters inside string row, '\r\n' - newline characters for create a new row.
 *
 * @author  Andrey Zaboychenko
 * @author  Roman Ratsun <r.ratsun.rollun@gmail.com>
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */
class CsvFileObject implements \IteratorAggregate
{
    /**
     * @var BaseFileObject
     */
    protected $fileObject;

    /**
     * @var string
     */
    protected $delimiter;

    /**
     * @var string
     */
    protected $enclosure;

    /**
     * @var string
     */
    protected $escape;

    /**
     * @var array|null
     */
    protected $columns = null;

    /**
     * @param string $filename
     * @param array  $columnsNames
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public static function createNewCsvFile(string $filename, array $columnsNames, $delimiter = ',', $enclosure = '"', $escape = '\\')
    {
        if (is_readable($filename)) {
            throw new InvalidArgumentException("There is readable file: $filename");
        }
        $fileObject = new BaseFileObject($filename);
        $fileObject->fputcsv($columnsNames, $delimiter, $enclosure, $escape);
    }

    /**
     * CsvFileObject constructor.
     *
     * @param string $filename
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape
     *
     * @throws InvalidArgumentException
     */
    public function __construct(string $filename, string $delimiter = ',', string $enclosure = '"', string $escape = '\\')
    {
        if (!is_readable($filename)) {
            throw new InvalidArgumentException("There is not readable file: $filename");
        }

        $this->fileObject = new BaseFileObject($filename);

        $this->delimiter = $delimiter;
        $this->enclosure = $enclosure;
        $this->escape = $escape;

        $this->fileObject->setFlags(SplFileObject::READ_CSV);
        $this->setControl($delimiter, $enclosure, $enclosure);
        $this->getColumns();
    }

    /**
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape
     *
     * @return void
     */
    protected function setControl(string $delimiter = ',', string $enclosure = '"', string $escape = '\\'): void
    {
        $this->fileObject->setCsvControl($delimiter, $enclosure, $escape);
    }

    /**
     * Get number of lines of file
     *
     * @return int
     */
    public function getNumberOfLines(): int
    {
        $this->fileObject->seek(PHP_INT_MAX);

        return $this->fileObject->key();
    }

    /**
     * @return BaseFileObject
     */
    public function getFileObject(): BaseFileObject
    {
        return $this->fileObject;
    }

    /**
     * @return array
     * @throws InvalidArgumentException
     */
    public function getColumns(): array
    {
        if (is_null($this->columns)) {
            $this->fileObject->lock(LOCK_SH);
            $this->fileObject->rewind();
            $current = $this->fileObject->current();
            $this->fileObject->unlock();
            if (!is_array($current)) {
                throw new InvalidArgumentException("There is not columns names in file: " . $this->fileObject->getRealPath());
            }
            $this->columns = $current;
        }

        return $this->columns;
    }

    /**
     * @return bool
     */
    public function hasData(): bool
    {
        $stringsCount = $this->fileObject->getStringsCount();

        return $stringsCount > 1;
    }

    /**
     * @param int $zeroBasedStringNumber
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public function getRow(int $zeroBasedStringNumber): array
    {
        $this->fileObject->lock(LOCK_SH);
        $stringsCount = $this->fileObject->getStringsCount();
        if ($stringsCount - 2 < $zeroBasedStringNumber) {
            throw new InvalidArgumentException(
                "\$zeroBasedStringNumber = $zeroBasedStringNumber .  Strings count with colums = $stringsCount \n in file: "
                . $this->fileObject->getRealPath()
            );
        }
        $this->fileObject->seek($zeroBasedStringNumber + 1);
        $row = $this->fileObject->current();
        $this->fileObject->unlock();

        return $row;
    }

    /**
     * @param array $dataArray
     *
     * @return int
     * @throws RollunException
     * @throws InvalidArgumentException
     */
    public function addRow(array $dataArray): int
    {
        $dataArray = $this->prepareFieldsBeforeAdd($dataArray);
        $this->fileObject->lock(LOCK_SH);
        $length = $this->fileObject->fputcsv($dataArray, $this->delimiter, $this->enclosure, $this->escape);
        if ($length === false) {
            $dataInJson = Serializer::jsonSerialize($dataArray);
            throw new InvalidArgumentException(
                "Can not write data:  $dataInJson \n in file: "
                . $this->fileObject->getRealPath()
            );
        }
        $this->fileObject->unlock();

        return (int)$length;
    }

    /**
     * Before write new line to CSV file we have to replace \r\n special chars to \n from fields
     *
     * @param array $dataArray
     *
     * @return array
     */
    protected function prepareFieldsBeforeAdd(array $dataArray): array
    {
        foreach ($dataArray as $key => $value) {
            $dataArray[$key] = str_replace("\r\n", "\n", $dataArray[$key]);
        }

        return $dataArray;
    }

    /**
     * Delete all rows of file
     */
    public function deleteAllRows(): void
    {
        $this->fileObject->lock(LOCK_SH);
        $this->fileObject->rewind();
        $this->fileObject->current();
        $t = $this->fileObject->ftell();
        $this->fileObject->truncateWithCheck($t);
        $this->fileObject->unlock();
    }

    /**
     * @inheritDoc
     */
    public function getIterator()
    {
        $this->fileObject->lock(LOCK_SH);
        $this->fileObject->rewind();
        $fileObject = $this->fileObject;
        $this->fileObject->current();
        $this->fileObject->next();
        while ($this->fileObject->valid()) {
            $row = $fileObject->current();
            if ($row === false) {
                break;
            } elseif ($row === [null]) {
                $this->fileObject->next();
                continue;
            }
            yield $row;
            $this->fileObject->next();
        }
        $this->fileObject->unlock();
    }

    /**
     * @return string
     */
    public function getDelimiter(): string
    {
        return $this->delimiter;
    }

    /**
     * @return string
     */
    public function getEnclosure(): string
    {
        return $this->enclosure;
    }

    /**
     * @return string
     */
    public function getEscape(): string
    {
        return $this->escape;
    }
}
