<?php

declare(strict_types=1);

namespace rollun\test\unit\DataStore\DataStore\Csv\Iterator;

use PHPUnit\Framework\TestCase;
use rollun\datastore\DataStore\CsvBase;
use rollun\test\unit\DataStore\DataStore\Csv\Support\AssertsNoDeprecationsTrait;
use RuntimeException;

/**
 * Documents that CsvBase::read() and CsvBase::getIterator() (which returns
 * CsvIterator) currently use different CSV escape semantics:
 *
 *  - CsvBase reads via SplFileObject configured with setCsvControl(..., escape: '')
 *    -> RFC 4180 mode, "" doubling for embedded quotes.
 *  - CsvIterator opens its own SplFileObject (Iterators/CsvIterator.php:39-41)
 *    and calls setCsvControl($delimiter) WITHOUT the escape: '' argument
 *    -> PHP-default \\ escape mode.
 *
 * For any field containing a quote, the two paths can return different values.
 * This is a real, currently-shipping bug. The merge plan fixes it by routing
 * both reads through a single configured SplFileObject (or through the same
 * ajgl/csv-rfc adapter), at which point the test below should be flipped from
 * markTestSkipped to an active assertion.
 */
final class CsvIteratorConsistencyTest extends TestCase
{
    use AssertsNoDeprecationsTrait;

    private string $tmpFile = '';

    protected function tearDown(): void
    {
        if ($this->tmpFile !== '' && is_file($this->tmpFile)) {
            unlink($this->tmpFile);
        }
        $this->tmpFile = '';
    }

    public function testReadAndIteratorAgreeOnPlainAsciiRow(): void
    {
        $this->startCapturingDeprecations();

        $csv = $this->makeEmptyCsv(['id', 'name']);
        $csv->create(['id' => 1, 'name' => 'plain']);

        $viaRead = $csv->read(1);
        $viaIterator = $this->firstRowFromIterator($csv);

        self::assertEquals($viaRead, $viaIterator);

        $this->assertNoDeprecationsCaptured();
    }

    public function testReadAndIteratorAgreeOnRowWithEmbeddedQuote(): void
    {
        $this->startCapturingDeprecations();

        $csv = $this->makeEmptyCsv(['id', 'name']);
        $csv->create(['id' => 1, 'name' => 'foo "bar" baz']);

        $viaRead = $csv->read(1);
        $viaIterator = $this->firstRowFromIterator($csv);

        self::assertEquals($viaRead, $viaIterator);

        $this->assertNoDeprecationsCaptured();
    }

    public function testReadAndIteratorAgreeOnRowWithEmbeddedNewline(): void
    {
        $this->startCapturingDeprecations();

        $csv = $this->makeEmptyCsv(['id', 'name']);
        $csv->create(['id' => 1, 'name' => "line1\nline2"]);

        $viaRead = $csv->read(1);
        $viaIterator = $this->firstRowFromIterator($csv);

        self::assertEquals($viaRead, $viaIterator);

        $this->assertNoDeprecationsCaptured();
    }

    private function firstRowFromIterator(CsvBase $csv): ?array
    {
        foreach ($csv->getIterator() as $row) {
            return $row;
        }
        return null;
    }

    private function makeEmptyCsv(array $columns): CsvBase
    {
        $this->tmpFile = tempnam(sys_get_temp_dir(), 'csv_iter_');
        if ($this->tmpFile === false) {
            throw new RuntimeException('tempnam failed');
        }

        $handle = fopen($this->tmpFile, 'w');
        \Ajgl\Csv\Rfc\CsvRfcUtils::fPutCsv($handle, $columns, ',');
        fclose($handle);

        return new CsvBase($this->tmpFile, ',');
    }
}
