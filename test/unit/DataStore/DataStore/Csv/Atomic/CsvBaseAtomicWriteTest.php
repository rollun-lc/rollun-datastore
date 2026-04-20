<?php

declare(strict_types=1);

namespace rollun\test\unit\DataStore\DataStore\Csv\Atomic;

use PHPUnit\Framework\TestCase;
use rollun\datastore\DataStore\CsvBase;
use rollun\test\unit\DataStore\DataStore\Csv\Support\AssertsNoDeprecationsTrait;
use RuntimeException;

/**
 * Locks down the contract that CsvBase::flush() must preserve the original
 * file when an error occurs mid-write. This is the property that allows the
 * datastore to be safely retried after a crash.
 *
 * The merge plan REPLACES the current copy()-based flush with a strict
 * tempfile + rename() pattern. This test must keep passing across that change
 * (and ideally cover even more edge cases — see merge plan §F).
 *
 * Implementation strategy: subclass CsvBase, override writeRow() to throw on
 * the Nth invocation, attempt an update(), and assert the original file is
 * byte-identical to its pre-crash snapshot.
 */
final class CsvBaseAtomicWriteTest extends TestCase
{
    use AssertsNoDeprecationsTrait;

    private string $filename = '';

    protected function setUp(): void
    {
        $this->filename = tempnam(sys_get_temp_dir(), 'csv_atomic_');
        if ($this->filename === false) {
            throw new RuntimeException('tempnam failed');
        }

        // Native fputcsv handles int id transparently; CsvRfcUtils::fPutCsv
        // would crash on non-string fields via strpos(). Bytes are identical
        // for the simple ASCII data we seed here.
        $handle = fopen($this->filename, 'w');
        fputcsv($handle, ['id', 'name'], ',', '"', '');
        fputcsv($handle, [1, 'foo'], ',', '"', '');
        fputcsv($handle, [2, 'bar'], ',', '"', '');
        fputcsv($handle, [3, 'baz'], ',', '"', '');
        fclose($handle);
    }

    protected function tearDown(): void
    {
        if ($this->filename !== '' && is_file($this->filename)) {
            unlink($this->filename);
        }
        $this->filename = '';
    }

    public function testCrashDuringFlushDoesNotCorruptOriginalFile(): void
    {
        $this->startCapturingDeprecations();

        $originalBytes = file_get_contents($this->filename);
        self::assertNotEmpty($originalBytes, 'precondition: file must have content');

        $csv = new class ($this->filename, ',') extends CsvBase {
            public int $writeRowCalls = 0;

            public function writeRow($fHandler, $row)
            {
                $this->writeRowCalls++;
                if ($this->writeRowCalls === 2) {
                    throw new RuntimeException('simulated mid-flush crash');
                }
                parent::writeRow($fHandler, $row);
            }
        };

        $exceptionPropagated = false;
        try {
            $csv->update(['id' => 2, 'name' => 'updated']);
        } catch (RuntimeException $e) {
            $exceptionPropagated = $e->getMessage() === 'simulated mid-flush crash';
        }

        self::assertTrue(
            $exceptionPropagated,
            'simulated crash exception must propagate out of CsvBase::flush()',
        );

        self::assertSame(
            $originalBytes,
            file_get_contents($this->filename),
            'Original file must remain byte-identical when flush() crashes mid-write',
        );

        $this->assertNoDeprecationsCaptured();
    }

    public function testSuccessfulFlushPreservesAllOtherRows(): void
    {
        $this->startCapturingDeprecations();

        $csv = new CsvBase($this->filename, ',');
        $csv->update(['id' => 2, 'name' => 'updated']);

        self::assertEquals(['id' => 1, 'name' => 'foo'], $csv->read(1));
        self::assertEquals(['id' => 2, 'name' => 'updated'], $csv->read(2));
        self::assertEquals(['id' => 3, 'name' => 'baz'], $csv->read(3));

        $this->assertNoDeprecationsCaptured();
    }
}
