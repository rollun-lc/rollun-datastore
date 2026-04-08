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
        self::markTestSkipped(
            'merge-pending: CsvBase::read() uses fgetcsv with empty escape (RFC), '
            . 'while CsvIterator opens its own SplFileObject in '
            . 'Iterators/CsvIterator.php:39-41 with default \\ escape. '
            . 'On a field containing a literal ", the two read paths return '
            . 'different values. Merge plan: unify the two read paths.',
        );

        $csv = $this->makeEmptyCsv(['id', 'name']);
        $csv->create(['id' => 1, 'name' => 'foo "bar" baz']);

        $viaRead = $csv->read(1);
        $viaIterator = $this->firstRowFromIterator($csv);

        self::assertEquals($viaRead, $viaIterator);
    }

    public function testReadAndIteratorAgreeOnRowWithEmbeddedNewline(): void
    {
        self::markTestSkipped(
            'merge-pending: CsvBase::read() opens SplFileObject with DROP_NEW_LINE '
            . '(CsvBase.php:363), which strips the embedded \n inside a quoted '
            . 'field, returning "line1line2" instead of "line1\nline2". '
            . 'CsvIterator opens its own SplFileObject WITHOUT DROP_NEW_LINE '
            . '(Iterators/CsvIterator.php:40), so it returns the correct '
            . '"line1\nline2". The two read paths therefore disagree on any '
            . 'multi-line value. Merge plan: drop DROP_NEW_LINE in CsvBase, '
            . 'unify the two read paths through ajgl/csv-rfc.',
        );

        $csv = $this->makeEmptyCsv(['id', 'name']);
        $csv->create(['id' => 1, 'name' => "line1\nline2"]);

        $viaRead = $csv->read(1);
        $viaIterator = $this->firstRowFromIterator($csv);

        self::assertEquals($viaRead, $viaIterator);
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
