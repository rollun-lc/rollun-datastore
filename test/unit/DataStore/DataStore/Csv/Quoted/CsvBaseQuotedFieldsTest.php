<?php

declare(strict_types=1);

namespace rollun\test\unit\DataStore\DataStore\Csv\Quoted;

use PHPUnit\Framework\TestCase;
use rollun\datastore\DataStore\CsvBase;
use rollun\test\unit\DataStore\DataStore\Csv\Support\AssertsNoDeprecationsTrait;
use RuntimeException;

/**
 * Reads CSV fixtures containing fields that exercise quoting / escape rules
 * (delimiter inside quoted field, RFC "" escape, legacy \" escape, embedded
 * newlines), and exercises full write→read round-trips for the same edge
 * cases.
 *
 * Tests that document KNOWN bugs are markTestSkipped with a "merge-pending"
 * reason and will be flipped to active assertions as the merge closes the
 * corresponding gap.
 */
final class CsvBaseQuotedFieldsTest extends TestCase
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

    // ----------------------------------------------------------------------
    // Read fixtures (byte-precise files committed next to this test)
    // ----------------------------------------------------------------------

    public function testReadFieldWithDelimiterInside(): void
    {
        $this->startCapturingDeprecations();

        $csv = new CsvBase(__DIR__ . '/delimiter_in_quoted_field.csv', ',');

        self::assertEquals(
            ['id' => 1, 'name' => 'a,b,c', 'note' => 'ok'],
            $csv->read(1),
        );
        self::assertEquals(
            ['id' => 2, 'name' => 'plain', 'note' => 'ok'],
            $csv->read(2),
        );

        $this->assertNoDeprecationsCaptured();
    }

    public function testReadFieldWithRfcDoubleQuoteEscape(): void
    {
        $this->startCapturingDeprecations();

        $csv = new CsvBase(__DIR__ . '/rfc_double_quote.csv', ',');

        self::assertEquals(
            ['id' => 1, 'name' => 'foo "bar" baz', 'note' => 'ok'],
            $csv->read(1),
        );
        self::assertEquals(
            ['id' => 2, 'name' => 'plain', 'note' => 'ok'],
            $csv->read(2),
        );

        $this->assertNoDeprecationsCaptured();
    }

    // testReadFieldWithLegacyBackslashEscape removed in M14: legacy
    // backslash-escaped CSV files (written by pre-merge CsvBase via native
    // fputcsv default escape) are no longer supported. Files with literal
    // " characters in fields written by older versions must be migrated
    // before reading — see bin/migrate-csv-escape.php.
    // The corresponding fixture legacy_backslash_quote.csv is also removed.

    public function testReadFieldWithEmbeddedLf(): void
    {
        $this->startCapturingDeprecations();

        $csv = new CsvBase(__DIR__ . '/embedded_lf_in_field.csv', ',');

        self::assertEquals(
            ['id' => 1, 'name' => "line1\nline2", 'note' => 'ok'],
            $csv->read(1),
        );
        self::assertEquals(
            ['id' => 2, 'name' => 'plain', 'note' => 'ok'],
            $csv->read(2),
        );

        $this->assertNoDeprecationsCaptured();
    }

    public function testReadFieldWithEmbeddedCrlf(): void
    {
        $this->startCapturingDeprecations();

        $csv = new CsvBase(__DIR__ . '/embedded_crlf_in_field.csv', ',');

        self::assertEquals(
            ['id' => 1, 'name' => "line1\r\nline2", 'note' => 'ok'],
            $csv->read(1),
        );
        self::assertEquals(
            ['id' => 2, 'name' => 'plain', 'note' => 'ok'],
            $csv->read(2),
        );

        $this->assertNoDeprecationsCaptured();
    }

    // ----------------------------------------------------------------------
    // Round-trip: create() → read() must preserve byte-exact field content
    // ----------------------------------------------------------------------

    public function testWriteThenReadFieldWithDelimiter(): void
    {
        $this->startCapturingDeprecations();

        $csv = $this->makeEmptyCsv(['id', 'name', 'note']);

        $csv->create($input = ['id' => 1, 'name' => 'a,b,c', 'note' => 'ok']);

        self::assertEquals($input, $csv->read(1));

        $this->assertNoDeprecationsCaptured();
    }

    public function testWriteThenReadFieldWithEmbeddedLf(): void
    {
        $this->startCapturingDeprecations();

        $csv = $this->makeEmptyCsv(['id', 'name', 'note']);
        $csv->create($input = ['id' => 1, 'name' => "line1\nline2", 'note' => 'ok']);

        self::assertEquals($input, $csv->read(1));

        $this->assertNoDeprecationsCaptured();
    }

    public function testWriteThenReadFieldWithDoubleQuote(): void
    {
        $this->startCapturingDeprecations();

        $csv = $this->makeEmptyCsv(['id', 'name', 'note']);
        $csv->create($input = ['id' => 1, 'name' => 'foo "bar" baz', 'note' => 'ok']);

        self::assertEquals($input, $csv->read(1));

        $this->assertNoDeprecationsCaptured();
    }

    public function testWriteThenReadFieldWithEmbeddedCrlf(): void
    {
        $this->startCapturingDeprecations();

        $csv = $this->makeEmptyCsv(['id', 'name', 'note']);
        $csv->create($input = ['id' => 1, 'name' => "line1\r\nline2", 'note' => 'ok']);

        self::assertEquals($input, $csv->read(1));

        $this->assertNoDeprecationsCaptured();
    }

    // ----------------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------------

    private function makeEmptyCsv(array $columns): CsvBase
    {
        $this->tmpFile = tempnam(sys_get_temp_dir(), 'csv_quoted_');
        if ($this->tmpFile === false) {
            throw new RuntimeException('tempnam failed');
        }

        $handle = fopen($this->tmpFile, 'w');
        // Write header via the same RFC-aware writer used by the merged CSV
        // path will use, so we don't bake the legacy escape into our fixture.
        \Ajgl\Csv\Rfc\CsvRfcUtils::fPutCsv($handle, $columns, ',');
        fclose($handle);

        return new CsvBase($this->tmpFile, ',');
    }
}
