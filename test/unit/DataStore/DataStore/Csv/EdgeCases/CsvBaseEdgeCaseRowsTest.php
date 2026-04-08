<?php

declare(strict_types=1);

namespace rollun\test\unit\DataStore\DataStore\Csv\EdgeCases;

use Ajgl\Csv\Rfc\CsvRfcUtils;
use PHPUnit\Framework\TestCase;
use rollun\datastore\DataStore\CsvBase;
use rollun\datastore\DataStore\DataStoreException;
use rollun\test\unit\DataStore\DataStore\Csv\Support\AssertsNoDeprecationsTrait;
use RuntimeException;

/**
 * Locks down behavior on edge-case rows: ragged (column count mismatch),
 * empty lines, header-only files, completely empty files.
 *
 * Where current behavior is "throws an undocumented PHP error", the test is
 * marked merge-pending and the desired contract is documented in the skip
 * reason. The merge plan should pick a contract (return null vs throw a typed
 * exception) and flip these to active assertions.
 */
final class CsvBaseEdgeCaseRowsTest extends TestCase
{
    use AssertsNoDeprecationsTrait;

    private string $filename = '';

    protected function setUp(): void
    {
        $this->filename = tempnam(sys_get_temp_dir(), 'csv_edge_');
        if ($this->filename === false) {
            throw new RuntimeException('tempnam failed');
        }
    }

    protected function tearDown(): void
    {
        if ($this->filename !== '' && is_file($this->filename)) {
            unlink($this->filename);
        }
        $this->filename = '';
    }

    public function testReadHeaderOnlyFile(): void
    {
        $this->startCapturingDeprecations();

        $this->writeBytes("id,name,note\n");

        $csv = new CsvBase($this->filename, ',');
        self::assertSame(0, $csv->count());
        self::assertNull($csv->read(1));

        $this->assertNoDeprecationsCaptured();
    }

    public function testReadHeaderOnlyFileAllowsCreate(): void
    {
        $this->startCapturingDeprecations();

        $this->writeBytes("id,name,note\n");

        $csv = new CsvBase($this->filename, ',');
        $csv->create(['id' => 1, 'name' => 'foo', 'note' => 'bar']);

        self::assertSame(1, $csv->count());
        self::assertEquals(
            ['id' => 1, 'name' => 'foo', 'note' => 'bar'],
            $csv->read(1),
        );

        $this->assertNoDeprecationsCaptured();
    }

    public function testReadFileWithBlankLineBetweenRows(): void
    {
        $this->startCapturingDeprecations();

        // SKIP_EMPTY flag in CsvBase::getFile() should drop the blank line.
        $this->writeBytes("id,name\n1,foo\n\n2,bar\n");

        $csv = new CsvBase($this->filename, ',');

        self::assertSame(2, $csv->count());
        self::assertEquals(['id' => 1, 'name' => 'foo'], $csv->read(1));
        self::assertEquals(['id' => 2, 'name' => 'bar'], $csv->read(2));

        $this->assertNoDeprecationsCaptured();
    }

    public function testReadRowWithFewerColumnsThanHeader(): void
    {
        $this->writeBytes("id,name,note\n1,onlyone\n");

        $csv = new CsvBase($this->filename, ',');

        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessageMatches('/Malformed CSV row .+ 2 fields, expected 3 columns/');

        $csv->read(1);
    }

    public function testReadRowWithMoreColumnsThanHeader(): void
    {
        $this->writeBytes("id,name\n1,foo,extra\n");

        $csv = new CsvBase($this->filename, ',');

        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessageMatches('/Malformed CSV row .+ 3 fields, expected 2 columns/');

        $csv->read(1);
    }

    public function testReadCompletelyEmptyFile(): void
    {
        $this->startCapturingDeprecations();

        $this->writeBytes('');

        $csv = new CsvBase($this->filename, ',');
        self::assertSame(0, $csv->count());
        self::assertNull($csv->read(1));

        $this->assertNoDeprecationsCaptured();
    }

    private function writeBytes(string $content): void
    {
        file_put_contents($this->filename, $content);
    }
}
