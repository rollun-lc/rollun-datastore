<?php

declare(strict_types=1);

namespace rollun\test\unit;

use PHPUnit\Framework\TestCase;

/**
 * Требования для воспроизведения:
 *  - PHP >= 8.1
 *  - пакет xiag/rql-parser версии 1.0.2 (или любой без : int у TokenStream::count)
 *
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
final class XiagRqlParserTest extends TestCase
{
    public function testTokenStreamTriggersPhp81SignatureError(): void
    {
        if (\PHP_VERSION_ID < 80100) {
            $this->markTestSkipped('Актуально только для PHP 8.1+');
        }

        // Commit this part to get FatalError with Xiag/Rql-Parser
        $this->markTestSkipped();

        //        $this->expectException(\ErrorException::class);
        //        $this->expectExceptionMessageMatches(
        //            '/TokenStream::count\(\).*Countable::count\(\): int/i'
        //        );

        set_error_handler([self::class, 'convertWarningsToExceptions']);

        try {
            new \Xiag\Rql\Parser\TokenStream([]);
        } finally {
            restore_error_handler();
        }
    }

    public static function convertWarningsToExceptions(
        int $severity,
        string $message,
        string $file,
        int $line
    ): bool {
        if ($severity === E_WARNING || $severity === E_DEPRECATED) {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        }
        return false;
    }
}
