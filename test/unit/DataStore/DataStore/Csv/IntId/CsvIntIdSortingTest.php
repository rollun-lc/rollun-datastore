<?php

declare(strict_types=1);

namespace rollun\test\unit\DataStore\DataStore\Csv\IntId;

use PHPUnit\Framework\TestCase;
use rollun\datastore\DataStore\CsvIntId;
use rollun\datastore\DataStore\DataStoreException;
use rollun\test\unit\DataStore\DataStore\Csv\Support\AssertsNoDeprecationsTrait;
use RuntimeException;

/**
 * Locks down CsvIntId's two distinguishing behaviors:
 *  1) flush() inserts new rows in sorted-by-id position (overrides CsvBase)
 *  2) generatePrimaryKey() returns last_id + 1
 *
 * Also locks down checkIntegrityData() which refuses to instantiate over a
 * file whose existing rows are not sorted ascending by id.
 *
 * These properties are not asserted anywhere in the existing test base
 * (the inherited CsvBase tests only ever multiCreate range(1,N), which is
 * already sorted, so they accidentally pass even on a broken sort).
 */
final class CsvIntIdSortingTest extends TestCase
{
    use AssertsNoDeprecationsTrait;

    private string $filename = '';

    protected function setUp(): void
    {
        $this->filename = tempnam(sys_get_temp_dir(), 'csv_intid_');
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

    public function testInsertBetweenExistingIdsKeepsFileSorted(): void
    {
        $this->startCapturingDeprecations();

        $this->seed([
            ['id' => 1, 'name' => 'one'],
            ['id' => 3, 'name' => 'three'],
            ['id' => 7, 'name' => 'seven'],
            ['id' => 9, 'name' => 'nine'],
        ]);

        $csv = new CsvIntId($this->filename, ',');
        $csv->create(['id' => 5, 'name' => 'five']);

        self::assertSame([1, 3, 5, 7, 9], $this->physicalIdOrder());

        $this->assertNoDeprecationsCaptured();
    }

    public function testInsertBeforeFirstId(): void
    {
        $this->startCapturingDeprecations();

        $this->seed([
            ['id' => 5, 'name' => 'five'],
            ['id' => 7, 'name' => 'seven'],
        ]);

        $csv = new CsvIntId($this->filename, ',');
        $csv->create(['id' => 1, 'name' => 'one']);

        self::assertSame([1, 5, 7], $this->physicalIdOrder());

        $this->assertNoDeprecationsCaptured();
    }

    public function testInsertAfterLastId(): void
    {
        $this->startCapturingDeprecations();

        $this->seed([
            ['id' => 1, 'name' => 'one'],
            ['id' => 5, 'name' => 'five'],
        ]);

        $csv = new CsvIntId($this->filename, ',');
        $csv->create(['id' => 10, 'name' => 'ten']);

        self::assertSame([1, 5, 10], $this->physicalIdOrder());

        $this->assertNoDeprecationsCaptured();
    }

    public function testGeneratePrimaryKeyOnFileWithOnlyHeader(): void
    {
        $this->startCapturingDeprecations();

        $this->seed([]); // header only

        $csv = new CsvIntId($this->filename, ',');
        $created = $csv->create(['name' => 'first']);

        self::assertSame(1, $created['id']);

        $this->assertNoDeprecationsCaptured();
    }

    public function testGeneratePrimaryKeyAfterPopulatedFile(): void
    {
        $this->startCapturingDeprecations();

        $this->seed([
            ['id' => 1, 'name' => 'one'],
            ['id' => 2, 'name' => 'two'],
            ['id' => 3, 'name' => 'three'],
        ]);

        $csv = new CsvIntId($this->filename, ',');
        $created = $csv->create(['name' => 'fourth']);

        self::assertSame(4, $created['id']);

        $this->assertNoDeprecationsCaptured();
    }

    public function testCheckIntegrityFailsOnUnsortedFile(): void
    {
        $this->startCapturingDeprecations();

        $this->seed([
            ['id' => 3, 'name' => 'three'],
            ['id' => 1, 'name' => 'one'],
            ['id' => 2, 'name' => 'two'],
        ]);

        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage('only a list ordered by id ASC');

        try {
            new CsvIntId($this->filename, ',');
        } finally {
            // Restore handler even on expected exception so the trait does
            // not leak state into the next test.
            $this->assertNoDeprecationsCaptured();
        }
    }

    /**
     * @param array<int, array<string, int|string>> $rows
     */
    private function seed(array $rows): void
    {
        // Native fputcsv stringifies int id transparently; CsvRfcUtils crashes
        // on non-string fields. Bytes are identical for simple ASCII.
        $h = fopen($this->filename, 'w');
        fputcsv($h, ['id', 'name'], ',', '"', '');
        foreach ($rows as $row) {
            fputcsv($h, array_values($row), ',', '"', '');
        }
        fclose($h);
    }

    /**
     * Reads the file directly (bypassing CsvBase) and returns the IDs in
     * the order they physically appear in the file. Used to verify sort
     * invariants without relying on the data store under test.
     *
     * @return int[]
     */
    private function physicalIdOrder(): array
    {
        $ids = [];
        $h = fopen($this->filename, 'r');
        fgetcsv($h, 0, ',', '"', ''); // skip header
        while (($row = fgetcsv($h, 0, ',', '"', '')) !== false) {
            if ($row !== null && isset($row[0])) {
                $ids[] = (int) $row[0];
            }
        }
        fclose($h);
        return $ids;
    }
}
