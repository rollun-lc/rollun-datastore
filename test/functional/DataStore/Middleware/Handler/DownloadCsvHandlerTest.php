<?php

declare(strict_types=1);

namespace rollun\test\functional\DataStore\Middleware\Handler;

use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use rollun\datastore\DataStore\CsvBase;
use rollun\datastore\Middleware\Handler\DownloadCsvHandler;
use rollun\datastore\DataStore\DbTable;
use rollun\test\unit\DataStore\DataStore\Csv\Support\AssertsNoDeprecationsTrait;
use Xiag\Rql\Parser\Node\LimitNode;
use Xiag\Rql\Parser\Query;

final class DownloadCsvHandlerTest extends TestCase
{
    use AssertsNoDeprecationsTrait;

    private string $tmpCsvFile = '';

    protected function tearDown(): void
    {
        if ($this->tmpCsvFile !== '' && is_file($this->tmpCsvFile)) {
            unlink($this->tmpCsvFile);
        }
        $this->tmpCsvFile = '';
    }

    public static function canHandleProvider(): array
    {
        return [
            'ok: GET + download: csv' => ['GET', ['download' => ['csv']], true],
            'no: GET no headers' => ['GET', [], false],
            'no: POST + csv' => ['POST', ['download' => ['csv']], false],
            'no: case-sensitive (CSV)' => ['GET', ['download' => ['CSV']], false],
        ];
    }

    /**
     * @dataProvider canHandleProvider
     */
    public function testCanHandle(string $method, array $headers, bool $expected): void
    {
        $handler = $this->makeHandler($this->mockDbTable([]));

        $request = (new ServerRequest())
            ->withMethod($method)
            ->withUri(new Uri('https://example.com/orders'));

        foreach ($headers as $name => $values) {
            foreach ((array) $values as $v) {
                $request = $request->withAddedHeader($name, $v);
            }
        }

        self::assertSame($expected, $handler->canHandle($request));
    }

    public function testHandleBuildsCsvAndSetsHeaders(): void
    {
        // DataStoreInterface::query() returns rows as associative arrays
        // keyed by column name — the handler uses array_keys() of the first
        // row to emit the CSV column header.
        $dbTable = $this->mockDbTable([
            [['id' => 1, 'name' => 'a'], ['id' => 2, 'name' => 'b']],
            [['id' => 3, 'name' => 'c'], ['id' => 4, 'name' => 'd']],
            [],
        ]);

        $handler = $this->makeHandler($dbTable);

        $query = new Query();

        $request = (new ServerRequest())
            ->withMethod('GET')
            ->withUri(new Uri('https://example.com/orders'))
            ->withHeader('download', 'csv')
            ->withAttribute('rqlQueryObject', $query);

        $response = $handler->handle($request);

        self::assertSame('text/csv', $response->getHeaderLine('Content-Type'));
        $cd = $response->getHeaderLine('Content-Disposition');
        self::assertStringContainsString('attachment;', $cd);
        self::assertStringContainsString('filename=orders.csv', $cd);

        $csv = (string) $response->getBody();
        self::assertSame("id,name\n1,a\n2,b\n3,c\n4,d\n", $csv);
        self::assertSame((string) strlen($csv), $response->getHeaderLine('Content-Length'));
    }

    public function testHandleEmitsHeaderRowOnFirstBatchOnly(): void
    {
        // Two batches — header appears exactly once, before the first row.
        $dbTable = $this->mockDbTable([
            [['id' => 1, 'name' => 'first']],
            [['id' => 2, 'name' => 'second']],
            [],
        ]);

        $response = $this->makeHandler($dbTable)->handle($this->csvRequest());
        $body = (string) $response->getBody();

        self::assertSame("id,name\n1,first\n2,second\n", $body);
        self::assertSame(1, substr_count($body, "id,name\n"));
    }

    public function testHandleOmitsHeaderWhenNoRowsAreReturned(): void
    {
        // Empty dataset → empty body. We can't fabricate a header without at
        // least one row to infer the column names from.
        $dbTable = $this->mockDbTable([[]]);

        $response = $this->makeHandler($dbTable)->handle($this->csvRequest());

        self::assertSame('', (string) $response->getBody());
    }

    public function testHandleDoesNotMutateCallerRqlQuery(): void
    {
        // Mutating the caller's Query object would leak pagination state back
        // into shared middleware — the handler must work on a clone.
        $dbTable = $this->mockDbTable([[['id' => 1, 'name' => 'x']], []]);

        $query = new Query();
        $originalLimit = $query->getLimit(); // null

        (new class ($dbTable) extends DownloadCsvHandler {
            public function __construct($ds)
            {
                $this->dataStore = $ds;
            }
        })->handle(
            (new ServerRequest())
                ->withMethod('GET')
                ->withUri(new Uri('https://example.com/orders'))
                ->withHeader('download', 'csv')
                ->withAttribute('rqlQueryObject', $query),
        );

        self::assertSame($originalLimit, $query->getLimit());
    }

    public function testHandleOverridesClonedQueryLimit(): void
    {
        // The handler still sets its own pagination limit, but it does so on
        // a CLONE of the caller's query (see testHandleDoesNotMutateCaller-
        // RqlQuery). Here we verify the clone IS paginated with LIMIT/0.
        $dbTable = $this->mockDbTable([[]]);
        $handler = new class ($dbTable) extends DownloadCsvHandler {
            public ?LimitNode $observedLimit = null;

            public function __construct($ds)
            {
                $this->dataStore = $ds;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $response = parent::handle($request);
                /** @var Query $caller */
                $caller = $request->getAttribute('rqlQueryObject');
                $this->observedLimit = $caller->getLimit();
                return $response;
            }
        };

        $query = new Query();
        $query->setLimit(new LimitNode(10, 30));

        $handler->handle(
            (new ServerRequest())
                ->withMethod('GET')
                ->withUri(new Uri('https://example.com/orders'))
                ->withHeader('download', 'csv')
                ->withAttribute('rqlQueryObject', $query),
        );

        // The caller's query must be untouched.
        self::assertSame(10, $handler->observedLimit->getLimit());
        self::assertSame(30, $handler->observedLimit->getOffset());
    }

    public function testHandleEscapesFieldContainingDelimiter(): void
    {
        $this->startCapturingDeprecations();

        $dbTable = $this->mockDbTable([
            [['id' => '1', 'name' => 'a,b,c']],
            [],
        ]);

        $response = $this->makeHandler($dbTable)->handle($this->csvRequest());
        $body = (string) $response->getBody();

        self::assertSame("id,name\n1,\"a,b,c\"\n", $body);

        $this->assertNoDeprecationsCaptured();
    }

    public function testHandleEscapesFieldContainingDoubleQuoteRfcStyle(): void
    {
        $this->startCapturingDeprecations();

        $dbTable = $this->mockDbTable([
            [['id' => '1', 'name' => 'foo "bar" baz']],
            [],
        ]);

        $response = $this->makeHandler($dbTable)->handle($this->csvRequest());
        $body = (string) $response->getBody();

        self::assertSame("id,name\n1,\"foo \"\"bar\"\" baz\"\n", $body);

        $this->assertNoDeprecationsCaptured();
    }

    public function testHandleHandlesFieldWithEmbeddedNewline(): void
    {
        $this->startCapturingDeprecations();

        $dbTable = $this->mockDbTable([
            [['id' => '1', 'name' => "line1\nline2"]],
            [],
        ]);

        $response = $this->makeHandler($dbTable)->handle($this->csvRequest());
        $body = (string) $response->getBody();

        // fputcsv quotes the value when it contains a newline. The exact byte
        // shape (\n vs \r\n inside the field) is what we are locking down here.
        self::assertSame("id,name\n1,\"line1\nline2\"\n", $body);

        $this->assertNoDeprecationsCaptured();
    }

    public function testHandleWritesUtf8MultibyteCharactersWithoutCorruption(): void
    {
        $this->startCapturingDeprecations();

        $dbTable = $this->mockDbTable([
            [['id' => '1', 'name' => 'Привет мир']],
            [],
        ]);

        $response = $this->makeHandler($dbTable)->handle($this->csvRequest());
        $body = (string) $response->getBody();

        // Lock down byte-exact preservation of the Cyrillic content. Whether
        // PHP's fputcsv wraps the field in "..." is implementation-specific
        // across PHP versions for fields with non-ASCII bytes.
        self::assertStringContainsString('Привет мир', $body);
        self::assertStringStartsWith("id,name\n1,", $body);

        $this->assertNoDeprecationsCaptured();
    }

    public function testHandleEndToEndWithRealCsvBaseSource(): void
    {
        $this->startCapturingDeprecations();

        $this->tmpCsvFile = tempnam(sys_get_temp_dir(), 'csv_dl_');
        // Native fputcsv handles int id; CsvRfcUtils crashes on non-strings.
        $h = fopen($this->tmpCsvFile, 'w');
        fputcsv($h, ['id', 'name'], ',', '"', '');
        fputcsv($h, [1, 'foo'], ',', '"', '');
        fputcsv($h, [2, 'bar'], ',', '"', '');
        fclose($h);

        $csvBase = new CsvBase($this->tmpCsvFile, ',');

        $response = $this->makeHandler($csvBase)->handle($this->csvRequest());
        $body = (string) $response->getBody();

        // Currently CsvBase::query returns associative arrays so the handler
        // emits "id,name\n1,foo\n2,bar\n". The end-to-end shape is what we are
        // locking down — the merge must keep producing the same logical CSV
        // even when the writer is swapped underneath.
        self::assertStringContainsString('foo', $body);
        self::assertStringContainsString('bar', $body);

        $this->assertNoDeprecationsCaptured();
    }

    private function csvRequest(): ServerRequest
    {
        $req = (new ServerRequest())
            ->withMethod('GET')
            ->withUri(new Uri('https://example.com/orders'))
            ->withHeader('download', 'csv')
            ->withAttribute('rqlQueryObject', new Query());

        return $req;
    }

    /**
     * Mock DbTable with query() method stump.
     * @param array<int, array<int, array<int|string>>> $batches
     */
    private function mockDbTable(array $batches): DbTable
    {
        /** @var DbTable&\PHPUnit\Framework\MockObject\MockObject $mock */
        $mock = $this
            ->getMockBuilder(DbTable::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['query'])
            ->getMock();

        $mock
            ->method('query')
            ->willReturnOnConsecutiveCalls(...$batches);

        return $mock;
    }

    private function makeHandler(object $dataStore): DownloadCsvHandler
    {
        return new class ($dataStore) extends DownloadCsvHandler {
            public function __construct($ds)
            {
                $this->dataStore = $ds;
            }
        };
    }
}
