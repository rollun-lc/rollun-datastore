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

    public function testReadFieldWithLegacyBackslashEscape(): void
    {
        self::markTestSkipped(
            'merge-pending: CsvBase configures fgetcsv with empty escape (RFC mode), '
            . 'so legacy backslash-escaped files written by native fputcsv default '
            . 'cannot be parsed. Decision needed: support legacy format via auto-detect, '
            . 'or document one-way migration. See merge plan §B.',
        );

        $csv = new CsvBase(__DIR__ . '/legacy_backslash_quote.csv', ',');

        self::assertEquals(
            ['id' => 1, 'name' => 'foo "bar" baz', 'note' => 'ok'],
            $csv->read(1),
        );
    }

    public function testReadFieldWithEmbeddedLf(): void
    {
        self::markTestSkipped(
            'merge-pending: CsvBase opens its SplFileObject with DROP_NEW_LINE '
            . '(CsvBase.php:363). That flag strips newlines from row terminators '
            . 'AND from inside quoted fields, so an embedded \n in a "..." value '
            . 'is silently lost on read. Run with the fixture verifies the value '
            . 'comes back as "line1line2" instead of "line1\nline2". '
            . 'Merge plan: drop DROP_NEW_LINE and handle row terminator trimming '
            . 'manually, OR switch to ajgl/csv-rfc on the read side too.',
        );

        $csv = new CsvBase(__DIR__ . '/embedded_lf_in_field.csv', ',');

        self::assertEquals(
            ['id' => 1, 'name' => "line1\nline2", 'note' => 'ok'],
            $csv->read(1),
        );
    }

    public function testReadFieldWithEmbeddedCrlf(): void
    {
        self::markTestSkipped(
            'merge-pending: same DROP_NEW_LINE bug as testReadFieldWithEmbeddedLf. '
            . 'CRLF inside a quoted field is also stripped. CsvBase.php:363.',
        );

        $csv = new CsvBase(__DIR__ . '/embedded_crlf_in_field.csv', ',');

        self::assertEquals(
            ['id' => 1, 'name' => "line1\r\nline2", 'note' => 'ok'],
            $csv->read(1),
        );
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
        self::markTestSkipped(
            'merge-pending: native fputcsv writes the embedded \n correctly into '
            . 'a quoted field, but CsvBase reads it back through SplFileObject '
            . 'with DROP_NEW_LINE (CsvBase.php:363) which strips that \n. The '
            . 'round-trip therefore corrupts the value. Same root cause as '
            . 'testReadFieldWithEmbeddedLf.',
        );

        $csv = $this->makeEmptyCsv(['id', 'name', 'note']);
        $csv->create($input = ['id' => 1, 'name' => "line1\nline2", 'note' => 'ok']);

        self::assertEquals($input, $csv->read(1));
    }

    public function testWriteThenReadFieldWithDoubleQuote(): void
    {
        self::markTestSkipped(
            'merge-pending: write path goes through native fputcsv (PHP-default \\\\ escape), '
            . 'read path goes through fgetcsv with escape: "" (RFC mode). '
            . 'Round-trip on a value containing literal " is asymmetric and corrupts data. '
            . 'Merge plan: route writes through ajgl/csv-rfc strPutCsv (RFC "" escape).',
        );

        $csv = $this->makeEmptyCsv(['id', 'name', 'note']);
        $csv->create($input = ['id' => 1, 'name' => 'foo "bar" baz', 'note' => 'ok']);

        self::assertEquals($input, $csv->read(1));
    }

    public function testWriteThenReadFieldWithEmbeddedCrlf(): void
    {
        self::markTestSkipped(
            'merge-pending: native fputcsv writes \r\n inside quoted fields verbatim, '
            . 'but the file row terminator is \n, so CRLF inside a field is '
            . 'indistinguishable from a row break in some readers. '
            . 'Merge plan: prepareFieldsBeforeAdd-style normalization (\r\n → \n) '
            . 'inside field values before writing, ported from rollun-files.',
        );

        $csv = $this->makeEmptyCsv(['id', 'name', 'note']);
        $csv->create($input = ['id' => 1, 'name' => "line1\r\nline2", 'note' => 'ok']);

        self::assertEquals($input, $csv->read(1));
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
