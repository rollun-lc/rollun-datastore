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
     * @return array
     */
    public function canHandleProvider()
    {
        return [
            // method, body, rqlQueryObject, primaryKeyValue, expected

            // Valid PUT request with array of records
            ['PUT', [['id' => 1, 'name' => 'test']], new RqlQuery(''), null, true],
            
            // Valid PUT with multiple records
            ['PUT', [['id' => 1, 'name' => 'a'], ['id' => 2, 'name' => 'b']], new RqlQuery(''), null, true],

            // Invalid: not PUT
            ['POST', [['id' => 1, 'name' => 'test']], new RqlQuery(''), null, false],

            // Invalid: not array of arrays
            ['PUT', ['id' => 1, 'name' => 'test'], new RqlQuery(''), null, false],

            // Invalid: simple array
            ['PUT', [1, 2, 3], new RqlQuery(''), null, false],

            // Invalid: has primaryKeyValue
            ['PUT', [['id' => 1, 'name' => 'test']], new RqlQuery(''), 123, false],

            // Invalid: has non-empty RQL query
            ['PUT', [['id' => 1, 'name' => 'test']], new RqlQuery('eq(name,test)'), null, false],

            // Invalid: array contains non-associative array
            ['PUT', [['id' => 1, 'name' => 'test'], [1, 2, 3]], new RqlQuery(''), null, false],

            // Valid: empty array
            ['PUT', [], new RqlQuery(''), null, true],
        ];
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

    /**
     * @return MultiUpdateHandler
     */
    protected function createHandler(): MultiUpdateHandler
    {
        $dataStore = $this->createDataStore();
        return new MultiUpdateHandler($dataStore);
    }

    public function createDataStore(): DataStoresInterface
    {
        $memory = new Memory(['id', 'name', 'value']);
        for ($i = 1; $i <= 4; $i++) {
            $memory->create(['id' => $i, 'name' => "name{$i}", 'value' => $i * 10], true);
        }
        return $memory;
    }

    public function testHandleSuccess()
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
        $this->assertNotNull($response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    private function getNullHandler()
    {
        return new class implements \Psr\Http\Server\RequestHandlerInterface {
            public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
            {
                return new \Laminas\Diactoros\Response();
            }
        };
    }
}

