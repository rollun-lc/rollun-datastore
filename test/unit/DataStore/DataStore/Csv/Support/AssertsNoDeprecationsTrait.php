<?php

declare(strict_types=1);

namespace rollun\test\unit\DataStore\DataStore\Csv\Support;

/**
 * Catches E_DEPRECATED / E_USER_DEPRECATED inside a test, regardless of the
 * global error_reporting() level set in test/bootstrap.php.
 *
 * Use:
 *   $this->startCapturingDeprecations();
 *   ... code under test ...
 *   $this->assertNoDeprecationsCaptured();
 *
 * Cleanup runs in an @after method, so even a failed assertion does not leak
 * the temporarily-elevated error_reporting() / installed error handler into
 * the next test in the same process.
 */
trait AssertsNoDeprecationsTrait
{
    /** @var string[] */
    private array $capturedDeprecations = [];

    private ?int $errorReportingBackup = null;

    /**
     * Patterns ignored as "not a CSV operation issue". Each pattern is a full
     * regex (with delimiters and anchors). Add to this list ONLY for class-
     * declaration / framework-level deprecations that the trait should not
     * surface as test failures.
     *
     * @var string[]
     */
    private array $ignoredDeprecationPatterns = [
        // CsvIterator does not declare PHP 8.1 return types on its Iterator
        // methods. Emitted at class declaration time, not by any CSV operation.
        // The merge will fix this by adding proper return types (or
        // #[\ReturnTypeWillChange] attributes) to CsvIterator.
        '/^Return type of .+ should either be compatible with /',
    ];

    /**
     * @after
     */
    protected function resetDeprecationCapture(): void
    {
        if ($this->errorReportingBackup !== null) {
            restore_error_handler();
            error_reporting($this->errorReportingBackup);
            $this->errorReportingBackup = null;
            $this->capturedDeprecations = [];
        }
    }

    protected function startCapturingDeprecations(): void
    {
        // Defensive: if a previous call leaked, reset first.
        if ($this->errorReportingBackup !== null) {
            $this->resetDeprecationCapture();
        }

        $this->capturedDeprecations = [];
        $this->errorReportingBackup = error_reporting();

        // bootstrap.php silences E_DEPRECATED at the error_reporting level,
        // so set_error_handler alone wouldn't see them. Re-enable globally.
        error_reporting(E_ALL);

        set_error_handler(
            function (int $errno, string $errstr): bool {
                foreach ($this->ignoredDeprecationPatterns as $pattern) {
                    if (preg_match($pattern, $errstr) === 1) {
                        return true; // swallow, not a CSV concern
                    }
                }
                $this->capturedDeprecations[] = $errstr;
                return true;
            },
            E_USER_DEPRECATED | E_DEPRECATED,
        );
    }

    protected function assertNoDeprecationsCaptured(): void
    {
        self::assertSame(
            [],
            $this->capturedDeprecations,
            "CSV operations emitted deprecation warning(s):\n - " . implode("\n - ", $this->capturedDeprecations),
        );
    }
}
