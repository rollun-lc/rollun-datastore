<?php

declare(strict_types=1);

namespace rollun\test\unit\DataStore\DataStore\Csv\Import\LineEndings;

use PHPUnit\Framework\TestCase;
use rollun\datastore\DataStore\CsvBase;
use rollun\test\unit\DataStore\DataStore\Csv\Support\AssertsNoDeprecationsTrait;

/**
 * Reads pre-built byte-precise fixtures with various line ending conventions
 * (LF, CRLF, CR, mixed) and a UTF-8 BOM, and asserts that CsvBase parses them
 * into the same logical rows.
 *
 * Tests that document KNOWN bugs are marked with markTestSkipped + a clear
 * "merge-pending" reason. They will be flipped to active assertions one by one
 * as the merge with rollun-files closes the corresponding gaps.
 *
 * Fixtures live next to this file. Each contains the same logical 3 rows:
 *   id,name,surname
 *   1,foo,bar
 *   2,baz,qux
 *   3,hello,world
 */
final class CsvBaseLineEndingsTest extends TestCase
{
    use AssertsNoDeprecationsTrait;

    /**
     * @var array<int, array<string, int|string>>
     */
    private const EXPECTED_ROWS = [
        ['id' => 1, 'name' => 'foo',   'surname' => 'bar'],
        ['id' => 2, 'name' => 'baz',   'surname' => 'qux'],
        ['id' => 3, 'name' => 'hello', 'surname' => 'world'],
    ];

    public function testReadLfBaseline(): void
    {
        $this->startCapturingDeprecations();

        $csv = new CsvBase(__DIR__ . '/lf.csv', ',');

        self::assertSame(3, $csv->count());
        self::assertEquals(self::EXPECTED_ROWS, $this->collectAll($csv));

        $this->assertNoDeprecationsCaptured();
    }

    public function testReadCrlf(): void
    {
        $this->startCapturingDeprecations();

        $csv = new CsvBase(__DIR__ . '/crlf.csv', ',');

        self::assertSame(3, $csv->count());
        self::assertEquals(self::EXPECTED_ROWS, $this->collectAll($csv));

        $this->assertNoDeprecationsCaptured();
    }

    public function testReadCrOnly(): void
    {
        self::markTestSkipped(
            'unsupported: Classic Mac (pre-OS X) CR-only line endings are not '
            . 'supported. PHP 8.1+ removed auto_detect_line_endings, and fgetcsv '
            . 'treats \r as part of field content. Implementing a stream filter '
            . 'is significant code for a vanishingly rare format. Documented in '
            . 'docs/index.md — users with CR-only files should pre-convert with '
            . "tr '\\r' '\\n' < in.csv > out.csv before passing to CsvBase.",
        );

        $csv = new CsvBase(__DIR__ . '/cr_only.csv', ',');

        self::assertSame(3, $csv->count());
        self::assertEquals(self::EXPECTED_ROWS, $this->collectAll($csv));
    }

    public function testReadMixedEndings(): void
    {
        $this->startCapturingDeprecations();

        $csv = new CsvBase(__DIR__ . '/mixed_endings.csv', ',');

        self::assertSame(3, $csv->count());
        self::assertEquals(self::EXPECTED_ROWS, $this->collectAll($csv));

        $this->assertNoDeprecationsCaptured();
    }

    public function testReadWithBomLf(): void
    {
        $this->startCapturingDeprecations();

        $csv = new CsvBase(__DIR__ . '/with_bom_lf.csv', ',');

        self::assertSame(3, $csv->count());
        self::assertEquals(self::EXPECTED_ROWS, $this->collectAll($csv));

        $this->assertNoDeprecationsCaptured();
    }

    public function testReadWithBomCrlf(): void
    {
        $this->startCapturingDeprecations();

        $csv = new CsvBase(__DIR__ . '/with_bom_crlf.csv', ',');

        self::assertSame(3, $csv->count());
        self::assertEquals(self::EXPECTED_ROWS, $this->collectAll($csv));

        $this->assertNoDeprecationsCaptured();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function collectAll(CsvBase $csv): array
    {
        $rows = [];
        foreach (self::EXPECTED_ROWS as $expected) {
            $row = $csv->read($expected['id']);
            if ($row !== null) {
                $rows[] = $row;
            }
        }
        return $rows;
    }
}
