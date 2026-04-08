<?php

declare(strict_types=1);

namespace rollun\test\unit\DataStore\DataStore\Csv\Encoding;

use Ajgl\Csv\Rfc\CsvRfcUtils;
use PHPUnit\Framework\TestCase;
use rollun\datastore\DataStore\CsvBase;
use rollun\test\unit\DataStore\DataStore\Csv\Support\AssertsNoDeprecationsTrait;
use RuntimeException;

/**
 * Locks down byte-exact UTF-8 preservation across the full write→read cycle.
 *
 * The merge will route writes through ajgl/csv-rfc strPutCsv, which uses
 * SplTempFileObject internally — this introduces an extra read/write step that
 * MUST not corrupt multi-byte sequences (Cyrillic, CJK, emoji).
 */
final class CsvBaseUtf8RoundtripTest extends TestCase
{
    use AssertsNoDeprecationsTrait;

    private string $filename = '';

    protected function setUp(): void
    {
        $this->filename = tempnam(sys_get_temp_dir(), 'csv_utf8_');
        if ($this->filename === false) {
            throw new RuntimeException('tempnam failed');
        }

        $h = fopen($this->filename, 'w');
        CsvRfcUtils::fPutCsv($h, ['id', 'name', 'note'], ',');
        fclose($h);
    }

    protected function tearDown(): void
    {
        if ($this->filename !== '' && is_file($this->filename)) {
            unlink($this->filename);
        }
        $this->filename = '';
    }

    public function testCyrillicRoundTrip(): void
    {
        $this->startCapturingDeprecations();

        $csv = new CsvBase($this->filename, ',');
        $csv->create([
            'id'   => 1,
            'name' => 'Пётр Иванов',
            'note' => 'комментарий',
        ]);

        $row = $csv->read(1);
        self::assertSame('Пётр Иванов', $row['name']);
        self::assertSame('комментарий', $row['note']);

        $this->assertNoDeprecationsCaptured();
    }

    public function testFourByteUtf8EmojiRoundTrip(): void
    {
        $this->startCapturingDeprecations();

        $csv = new CsvBase($this->filename, ',');
        // Emoji is a 4-byte UTF-8 sequence — historically the most common
        // place for charset bugs in PHP CSV libraries.
        $csv->create([
            'id'   => 1,
            'name' => '🚀 launch',
            'note' => '✨ shiny ✨',
        ]);

        $row = $csv->read(1);
        self::assertSame('🚀 launch', $row['name']);
        self::assertSame('✨ shiny ✨', $row['note']);

        $this->assertNoDeprecationsCaptured();
    }

    public function testCjkRoundTrip(): void
    {
        $this->startCapturingDeprecations();

        $csv = new CsvBase($this->filename, ',');
        $csv->create([
            'id'   => 1,
            'name' => '日本語のテスト',
            'note' => '中文测试',
        ]);

        $row = $csv->read(1);
        self::assertSame('日本語のテスト', $row['name']);
        self::assertSame('中文测试', $row['note']);

        $this->assertNoDeprecationsCaptured();
    }

    public function testCyrillicSurvivesUpdateCycle(): void
    {
        $this->startCapturingDeprecations();

        $csv = new CsvBase($this->filename, ',');
        $csv->create(['id' => 1, 'name' => 'старое', 'note' => 'хорошо']);
        $csv->update(['id' => 1, 'name' => 'новое']);

        $row = $csv->read(1);
        self::assertSame('новое',  $row['name']);
        self::assertSame('хорошо', $row['note']);

        $this->assertNoDeprecationsCaptured();
    }
}
