<?php

declare(strict_types=1);

namespace rollun\test\functional\DataStore\Middleware\Handler;

use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Uri;
use PHPUnit\Framework\TestCase;
use rollun\datastore\Middleware\Handler\DownloadCsvHandler;
use rollun\datastore\DataStore\DbTable;
use Xiag\Rql\Parser\Node\LimitNode;
use Xiag\Rql\Parser\Query;

final class DownloadCsvHandlerTest extends TestCase
{
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
        $dbTable = $this->mockDbTable([
            [[1, 'a'], [2, 'b']],
            [[3, 'c'], [4, 'd']],
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
        self::assertSame("1,a\n2,b\n3,c\n4,d\n", $csv);
        self::assertSame((string) strlen($csv), $response->getHeaderLine('Content-Length'));
    }

    public function testHandleMutatesOriginalRqlLimit(): void
    {
        $dbTable = $this->mockDbTable([[]]);
        $handler = $this->makeHandler($dbTable);

        $query = new Query();
        $query->setLimit(new LimitNode(10, 30));

        $req = (new ServerRequest())
            ->withMethod('GET')
            ->withUri(new Uri('https://example.com/orders'))
            ->withHeader('download', 'csv')
            ->withAttribute('rqlQueryObject', $query);

        $handler->handle($req);

        $limit = $query->getLimit();
        self::assertSame(DownloadCsvHandler::LIMIT, $limit->getLimit());
        self::assertSame(0, $limit->getOffset());
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
            ->setMethods(['query'])
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
