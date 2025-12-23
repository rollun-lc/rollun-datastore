<?php

declare(strict_types=1);

namespace rollun\test\functional\DataStore\Middleware\Handler;

use PHPUnit\Framework\MockObject\MockObject;
use rollun\datastore\DataStore\HttpClient;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use rollun\datastore\DataStore\Memory;
use rollun\datastore\Middleware\Handler\MultiUpdateHandler;
use rollun\datastore\Rql\RqlQuery;
use Laminas\Diactoros\ServerRequest;

/**
 * Test for MultiUpdateHandler
 */
class MultiUpdateHandlerTest extends BaseHandlerTest
{
    /**
     * @return \Generator
     */
    public function canHandleProvider(): \Generator
    {
        // method, body, rqlQueryObject, primaryKeyValue, expected

        // Valid PUT request with array of records
        yield 'valid single record' => ['PUT', [['id' => 1, 'name' => 'test']], new RqlQuery(), null, true];

        // Valid PUT with multiple records
        yield 'valid multiple records' => ['PUT', [['id' => 1, 'name' => 'a'], ['id' => 2, 'name' => 'b']], new RqlQuery(), null, true];

        // Invalid: not PUT
        yield 'invalid method POST' => ['POST', [['id' => 1, 'name' => 'test']], new RqlQuery(), null, false];
        yield 'invalid method PATCH' => ['PATCH', [['id' => 1, 'name' => 'test']], new RqlQuery(), null, false];
        yield 'invalid method GET' => ['GET', [['id' => 1, 'name' => 'test']], new RqlQuery(), null, false];

        // Invalid: not array of arrays (single record)
        yield 'invalid single record' => ['PUT', ['id' => 1, 'name' => 'test'], new RqlQuery(), null, false];

        // Invalid: simple array
        yield 'invalid list array' => ['PUT', [1, 2, 3], new RqlQuery(), null, false];

        // Invalid: has primaryKeyValue
        yield 'invalid with primaryKeyValue' => ['PUT', [['id' => 1, 'name' => 'test']], new RqlQuery(), 123, false];

        // Invalid: has non-empty RQL query
        yield 'invalid with RQL query' => ['PUT', [['id' => 1, 'name' => 'test']], new RqlQuery('eq(name,test)'), null, false];

        // Invalid: array contains non-associative array
        yield 'invalid mixed arrays' => ['PUT', [['id' => 1, 'name' => 'test'], [1, 2, 3]], new RqlQuery(), null, false];

        // Invalid: empty array
        yield 'invalid empty array' => ['PUT', [], new RqlQuery(), null, false];

        // Invalid: null body
        yield 'invalid null body' => ['PUT', null, new RqlQuery(), null, false];

        // Invalid: contains empty array
        yield 'invalid contains empty array' => ['PUT', [[], ['id' => 1, 'name' => 'test']], new RqlQuery(), null, false];

        // Invalid: first element is empty array
        yield 'invalid first element empty' => ['PUT', [[], ['id' => 2, 'name' => 'test']], new RqlQuery(), null, false];

        // Invalid: all elements are empty arrays
        yield 'invalid all empty arrays' => ['PUT', [[], []], new RqlQuery(), null, false];

        // Invalid: associative outer array
        yield 'invalid associative outer' => ['PUT', ['a' => ['id' => 1, 'name' => 'test']], new RqlQuery(), null, false];
    }

    /**
     * @param string $method
     * @param mixed $body
     * @param mixed $rqlQuery
     * @param mixed $primaryKeyValue
     * @param bool $expected
     * @dataProvider canHandleProvider
     */
    public function testCanHandle($method, $body, $rqlQuery, $primaryKeyValue, $expected)
    {
        $request = (new ServerRequest())
            ->withMethod($method)
            ->withParsedBody($body)
            ->withAttribute('rqlQueryObject', $rqlQuery)
            ->withAttribute('primaryKeyValue', $primaryKeyValue);

        $handler = $this->createHandler();
        $this->assertSame($expected, $handler->canHandle($request));
    }

    protected function createHandler(): MultiUpdateHandler
    {
        return new MultiUpdateHandler($this->createMock(DataStoresInterface::class));
    }

    public function testHandleSuccessWithMock()
    {
        $records = [
            ['id' => 1, 'name' => 'updated1'],
            ['id' => 2, 'name' => 'updated2'],
        ];

        /** @var DataStoresInterface|MockObject $dataStore */
        $dataStore = $this->createMock(HttpClient::class);

        $dataStore->expects($this->once())
            ->method('multiUpdate')
            ->with($records)
            ->willReturn([1, 2]);

        $handler = new MultiUpdateHandler($dataStore);

        $request = (new ServerRequest())
            ->withMethod('PUT')
            ->withParsedBody($records)
            ->withAttribute('rqlQueryObject', new RqlQuery(''))
            ->withAttribute('primaryKeyValue', null);

        $response = $handler->process($request, $this->getNullHandler());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));

        $body = json_decode((string) $response->getBody(), true);
        $this->assertEquals([1, 2], $body);
    }

    public function testHandleSuccessWithRealDataStore()
    {
        $memory = new Memory(['id', 'name', 'value']);
        $memory->create(['id' => 1, 'name' => 'name1', 'value' => 10]);
        $memory->create(['id' => 2, 'name' => 'name2', 'value' => 20]);

        $handler = new MultiUpdateHandler($memory);

        $records = [
            ['id' => 1, 'name' => 'updated1'],
            ['id' => 2, 'value' => 99],
        ];

        $request = (new ServerRequest())
            ->withMethod('PUT')
            ->withParsedBody($records)
            ->withAttribute('rqlQueryObject', new RqlQuery(''))
            ->withAttribute('primaryKeyValue', null);

        $response = $handler->process($request, $this->getNullHandler());

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true);
        $this->assertEquals([1, 2], $body);

        // Verify actual data was updated
        $this->assertEquals('updated1', $memory->read(1)['name']);
        $this->assertEquals(10, $memory->read(1)['value']); // unchanged
        $this->assertEquals('name2', $memory->read(2)['name']); // unchanged
        $this->assertEquals(99, $memory->read(2)['value']);
    }

    public function testHandleDataStoreException()
    {
        /** @var DataStoresInterface|MockObject $dataStore */
        $dataStore = $this->createMock(HttpClient::class);

        $dataStore->expects($this->once())
            ->method('multiUpdate')
            ->willThrowException(new \rollun\datastore\DataStore\DataStoreException('Update failed'));

        $handler = new MultiUpdateHandler($dataStore);

        $request = (new ServerRequest())
            ->withMethod('PUT')
            ->withParsedBody([['id' => 1, 'name' => 'test']])
            ->withAttribute('rqlQueryObject', new RqlQuery(''))
            ->withAttribute('primaryKeyValue', null);

        $this->expectException(\rollun\datastore\DataStore\DataStoreException::class);
        $this->expectExceptionMessage('Update failed');

        $handler->process($request, $this->getNullHandler());
    }

    private function getNullHandler(): \Psr\Http\Server\RequestHandlerInterface
    {
        return new class implements \Psr\Http\Server\RequestHandlerInterface {
            public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
            {
                return new \Laminas\Diactoros\Response();
            }
        };
    }
}
