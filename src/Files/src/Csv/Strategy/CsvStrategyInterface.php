<?php
declare(strict_types=1);

namespace rollun\files\Csv\Strategy;

use rollun\files\Csv\CsvFileObjectWithPrKey;

/**
 * Class CsvStrategyInterface
 *
 * @author  Roman Ratsun <r.ratsun.rollun@gmail.com>
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */
interface CsvStrategyInterface
{
    /**
     * @param CsvFileObjectWithPrKey $fileObjectWithPrKey
     * @param int                    $idColumn
     */
    public function __construct(CsvFileObjectWithPrKey $fileObjectWithPrKey, int $idColumn);

    /**
     * @param int $id
     *
     * @return array|null
     */
    public function getRowById(string $id): ?array;

    /**
     * @param array $row
     *
     * @return int
     */
    public function addRow(array $row): int;

    /**
     * @param array $row
     *
     * @return int
     */
    public function setRow(array $row): int;
}
