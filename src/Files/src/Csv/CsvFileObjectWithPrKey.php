<?php
declare(strict_types=1);

namespace rollun\files\Csv;

use rollun\files\Csv\Strategy\CsvBinaryStrategy;
use rollun\files\Csv\Strategy\CsvStrategyInterface;
use InvalidArgumentException;

/**
 * Class CsvFileObjectWithPrKey
 *
 * File specifications:
 *  1) ID is always the first column
 *  2) ID is always a string
 *
 * @author  Roman Ratsun <r.ratsun.rollun@gmail.com>
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */
class CsvFileObjectWithPrKey extends CsvFileObject
{
    /**
     * @var CsvStrategyInterface
     */
    protected $strategy;

    /**
     * @param string $filename
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape
     * @param string $strategyClass
     * @param string $identifier
     */
    public function __construct(
        string $filename,
        string $delimiter = ',',
        string $enclosure = '"',
        string $escape = '\\',
        string $strategyClass = CsvBinaryStrategy::class,
        string $identifier = 'id'
    ) {
        parent::__construct($filename, $delimiter, $enclosure, $escape);

        if (!class_exists($strategyClass)) {
            throw new InvalidArgumentException("CsvStrategy does not exist");
        }

        if (!in_array($identifier, $this->columns)) {
            throw new InvalidArgumentException('No such column');
        }

        $this->strategy = new $strategyClass($this, array_search($identifier, $this->columns));

        if (!$this->strategy instanceof CsvStrategyInterface) {
            throw new InvalidArgumentException('CsvStrategy should be instanceof CsvStrategyInterface');
        }
    }

    /**
     * @param string $id
     *
     * @return array|null
     */
    public function getRowById(string $id): ?array
    {
        return $this->strategy->getRowById($id);
    }

    /**
     * @inheritDoc
     */
    public function addRow(array $row): int
    {
        return $this->strategy->addRow($row);
    }

    /**
     * @inheritDoc
     */
    public function setRow(array $row): int
    {
        return $this->strategy->setRow($row);
    }
}
