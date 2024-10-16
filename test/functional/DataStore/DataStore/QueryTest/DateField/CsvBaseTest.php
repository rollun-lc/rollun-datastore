<?php

namespace rollun\test\functional\DataStore\DataStore\QueryTest\DateField;

use rollun\datastore\DataStore\CsvBase;
use rollun\datastore\DataStore\Interfaces\DataStoreInterface;

class CsvBaseTest extends BaseTest
{
    /**
     * @var CsvBase
     */
    private $csvBase;

    protected function getDataStore(): DataStoreInterface
    {
        if ($this->csvBase === null) {
            $this->csvBase = $this->setUpCsvBase();
        }
        return $this->csvBase;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $path = __DIR__ . '/' . self::TABLE_NAME . '.csv';
        if (file_exists($path)) {
            unlink($path);
        }
    }

    private function setUpCsvBase(): CsvBase
    {
        $path = __DIR__ . '/' . self::TABLE_NAME . '.csv';
        if (file_exists($path)) {
            unlink($path);
        }

        $file = fopen($path, 'w');
        fputcsv($file, [self::ID_NAME, self::FIELD_NAME]);
        fclose($file);

        return new CsvBase($path, ',');
    }
}
