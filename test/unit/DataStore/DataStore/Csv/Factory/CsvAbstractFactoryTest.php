<?php

declare(strict_types=1);

namespace rollun\test\unit\DataStore\DataStore\Csv\Factory;

use Ajgl\Csv\Rfc\CsvRfcUtils;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use rollun\datastore\DataStore\CsvBase;
use rollun\datastore\DataStore\CsvIntId;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\Factory\CsvAbstractFactory;
use rollun\test\unit\DataStore\DataStore\Csv\Support\AssertsNoDeprecationsTrait;
use RuntimeException;

/**
 * Smoke tests for CsvAbstractFactory. Until now this factory had ZERO test
 * coverage, even though it is the canonical entry point for instantiating
 * CsvBase / CsvIntId via the DI container.
 *
 * The merge may need to rewire the factory (e.g. inject a writer adapter,
 * accept new config keys for encoding/BOM/EOL). These tests pin the current
 * contract so that any change is intentional.
 */
final class CsvAbstractFactoryTest extends TestCase
{
    use AssertsNoDeprecationsTrait;

    private string $filename = '';

    protected function setUp(): void
    {
        $this->filename = tempnam(sys_get_temp_dir(), 'csv_factory_');
        if ($this->filename === false) {
            throw new RuntimeException('tempnam failed');
        }

        $h = fopen($this->filename, 'w');
        CsvRfcUtils::fPutCsv($h, ['id', 'name'], ',');
        fclose($h);
    }

    protected function tearDown(): void
    {
        if ($this->filename !== '' && is_file($this->filename)) {
            unlink($this->filename);
        }
        $this->filename = '';
    }

    public function testInvokeBuildsCsvBaseFromConfig(): void
    {
        $this->startCapturingDeprecations();

        $container = $this->containerWithConfig([
            'dataStore' => [
                'myCsv' => [
                    'class'     => CsvBase::class,
                    'filename'  => $this->filename,
                    'delimiter' => ',',
                ],
            ],
        ]);

        $instance = (new CsvAbstractFactory())($container, 'myCsv');

        self::assertInstanceOf(CsvBase::class, $instance);
        self::assertSame(',', $instance->getCsvDelimiter());
        self::assertSame($this->filename, $instance->getFilename());

        $this->assertNoDeprecationsCaptured();
    }

    public function testInvokeBuildsCsvIntIdFromConfig(): void
    {
        $this->startCapturingDeprecations();

        $container = $this->containerWithConfig([
            'dataStore' => [
                'myIntCsv' => [
                    'class'     => CsvIntId::class,
                    'filename'  => $this->filename,
                    'delimiter' => ',',
                ],
            ],
        ]);

        $instance = (new CsvAbstractFactory())($container, 'myIntCsv');

        self::assertInstanceOf(CsvIntId::class, $instance);

        $this->assertNoDeprecationsCaptured();
    }

    public function testInvokeUsesNullDelimiterWhenOmittedFromConfig(): void
    {
        $this->startCapturingDeprecations();

        // CsvBase constructor falls back to DEFAULT_DELIMITER (';') when
        // null is passed. The factory currently passes through null
        // unchanged via $serviceConfig['delimiter'] ?? null.
        // Seed the file with the default delimiter so the constructor's
        // getHeaders() call succeeds.
        $h = fopen($this->filename, 'w');
        CsvRfcUtils::fPutCsv($h, ['id', 'name'], ';');
        fclose($h);

        $container = $this->containerWithConfig([
            'dataStore' => [
                'myCsv' => [
                    'class'    => CsvBase::class,
                    'filename' => $this->filename,
                ],
            ],
        ]);

        $instance = (new CsvAbstractFactory())($container, 'myCsv');

        self::assertSame(';', $instance->getCsvDelimiter());

        $this->assertNoDeprecationsCaptured();
    }

    public function testInvokeThrowsWhenFilenameMissingFromConfig(): void
    {
        $container = $this->containerWithConfig([
            'dataStore' => [
                'broken' => [
                    'class' => CsvBase::class,
                    // no 'filename'
                ],
            ],
        ]);

        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage("file name for 'broken' is not specified");

        (new CsvAbstractFactory())($container, 'broken');
    }

    public function testCanCreateAcceptsCsvBaseConfig(): void
    {
        $this->startCapturingDeprecations();

        $container = $this->containerWithConfig([
            'dataStore' => [
                'myCsv' => [
                    'class'    => CsvBase::class,
                    'filename' => $this->filename,
                ],
            ],
        ]);

        self::assertTrue((new CsvAbstractFactory())->canCreate($container, 'myCsv'));

        $this->assertNoDeprecationsCaptured();
    }

    public function testCanCreateRejectsUnconfiguredService(): void
    {
        $this->startCapturingDeprecations();

        $container = $this->containerWithConfig([
            'dataStore' => [],
        ]);

        self::assertFalse((new CsvAbstractFactory())->canCreate($container, 'unknown'));

        $this->assertNoDeprecationsCaptured();
    }

    private function containerWithConfig(array $config): ContainerInterface
    {
        return new class ($config) implements ContainerInterface {
            public function __construct(private array $config) {}

            public function get(string $id): mixed
            {
                if ($id === 'config') {
                    return $this->config;
                }
                throw new \RuntimeException("Unexpected container lookup: $id");
            }

            public function has(string $id): bool
            {
                return $id === 'config';
            }
        };
    }
}
