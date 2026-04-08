<?php

declare(strict_types=1);

namespace rollun\test\unit\DataStore\DataStore\Csv\Types;

use Ajgl\Csv\Rfc\CsvRfcUtils;
use PHPUnit\Framework\TestCase;
use rollun\datastore\DataStore\CsvBase;
use rollun\test\unit\DataStore\DataStore\Csv\Support\AssertsNoDeprecationsTrait;
use RuntimeException;

/**
 * Locks down the type-coercion contract that CsvBase implements via writeRow()
 * and getTrueRow():
 *  - null  ↔ empty CSV cell
 *  - ''    ↔ literal "" (CsvBase's documented sentinel for "real empty string")
 *  - true  → written as 1, read back as int 1 (loose-equals true)
 *  - false → written as 0, read back as int 0 (loose-equals false)
 *  - numeric strings → int / float
 *  - leading-zero strings ("01") → preserved as string
 *
 * These cases were previously covered only by test/old/CsvBaseTest, which is
 * markTestSkipped on PHP 8.0. We're on PHP 8.1+, so they belong in the active
 * unit suite. The merge MUST preserve this contract — any deviation breaks
 * existing CSV files.
 */
final class CsvBaseTypeRoundtripTest extends TestCase
{
    use AssertsNoDeprecationsTrait;

    private string $filename = '';

    protected function setUp(): void
    {
        $this->filename = tempnam(sys_get_temp_dir(), 'csv_types_');
        if ($this->filename === false) {
            throw new RuntimeException('tempnam failed');
        }

        $h = fopen($this->filename, 'w');
        CsvRfcUtils::fPutCsv($h, ['id', 'value', 'note'], ',');
        fclose($h);
    }

    protected function tearDown(): void
    {
        if ($this->filename !== '' && is_file($this->filename)) {
            unlink($this->filename);
        }
        $this->filename = '';
    }

    public function testNullValueRoundTrip(): void
    {
        // NOTE: deliberately NO startCapturingDeprecations here.
        // CsvBase::getTrueRow (CsvBase.php:520) calls strlen($item) on $item
        // that has just been set to null in the previous if-branch — this
        // emits a PHP 8.1 "Passing null to strlen()" deprecation. The data
        // contract (null round-trips) works; the deprecation is a separate
        // bug to fix in the merge.
        // TODO(merge): fix CsvBase::getTrueRow to skip null items before strlen().

        $csv = new CsvBase($this->filename, ',');
        $csv->create(['id' => 1, 'value' => null, 'note' => 'ok']);

        $row = $csv->read(1);
        self::assertNull($row['value']);
    }

    public function testEmptyStringRoundTrip(): void
    {
        $this->startCapturingDeprecations();

        $csv = new CsvBase($this->filename, ',');
        $csv->create(['id' => 1, 'value' => '', 'note' => 'ok']);

        $row = $csv->read(1);
        self::assertSame('', $row['value']);

        $this->assertNoDeprecationsCaptured();
    }

    public function testNullAndEmptyStringAreDistinguishable(): void
    {
        // NOTE: see testNullValueRoundTrip — same strlen(null) deprecation
        // path. Data contract works, deprecation is merge-pending.

        $csv = new CsvBase($this->filename, ',');
        $csv->create(['id' => 1, 'value' => null, 'note' => 'null-row']);
        $csv->create(['id' => 2, 'value' => '',   'note' => 'empty-row']);

        self::assertNull($csv->read(1)['value']);
        self::assertSame('', $csv->read(2)['value']);
    }

    public function testTrueRoundTripsAsIntOne(): void
    {
        $this->startCapturingDeprecations();

        $csv = new CsvBase($this->filename, ',');
        $csv->create(['id' => 1, 'value' => true, 'note' => 'ok']);

        $row = $csv->read(1);
        // Documented contract: bool true is stored as 1, read back as int 1.
        // assertEquals (loose) deliberately, to match the historical contract.
        self::assertEquals(true, $row['value']);
        self::assertSame(1, $row['value']);

        $this->assertNoDeprecationsCaptured();
    }

    public function testFalseRoundTripsAsIntZero(): void
    {
        $this->startCapturingDeprecations();

        $csv = new CsvBase($this->filename, ',');
        $csv->create(['id' => 1, 'value' => false, 'note' => 'ok']);

        $row = $csv->read(1);
        self::assertEquals(false, $row['value']);
        self::assertSame(0, $row['value']);

        $this->assertNoDeprecationsCaptured();
    }

    public function testIntegerStringIsCoercedToInt(): void
    {
        $this->startCapturingDeprecations();

        $csv = new CsvBase($this->filename, ',');
        $csv->create(['id' => 1, 'value' => '42', 'note' => 'ok']);

        self::assertSame(42, $csv->read(1)['value']);

        $this->assertNoDeprecationsCaptured();
    }

    public function testFloatStringIsCoercedToFloat(): void
    {
        $this->startCapturingDeprecations();

        $csv = new CsvBase($this->filename, ',');
        $csv->create(['id' => 1, 'value' => '3.14', 'note' => 'ok']);

        self::assertSame(3.14, $csv->read(1)['value']);

        $this->assertNoDeprecationsCaptured();
    }

    public function testLeadingZeroStringIsPreservedAsString(): void
    {
        $this->startCapturingDeprecations();

        $csv = new CsvBase($this->filename, ',');
        $csv->create(['id' => 1, 'value' => '0123', 'note' => 'zip-code']);

        // Critical for ZIP codes, phone numbers, etc.
        self::assertSame('0123', $csv->read(1)['value']);

        $this->assertNoDeprecationsCaptured();
    }
}
