<?php

declare(strict_types=1);

namespace rollun\test\functional\DataStore\Middleware\Handler;

use PHPUnit\Framework\MockObject\MockObject;
use rollun\datastore\DataStore\HttpClient;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use rollun\datastore\DataStore\Memory;
use rollun\datastore\Middleware\Handler\QueriedUpdateHandler;
use rollun\datastore\Rql\RqlQuery;
use Laminas\Diactoros\ServerRequest;

class QueriedUpdateHandlerTest extends BaseHandlerTest
{
    public function canHandleProvider()
    {
        return [
            // method, body, rqlQueryObject, primaryKeyValue, expected

            // Верный PATCH запрос с фильтром, ассоц массивом и без id
            ['PATCH', ['foo' => 11], new RqlQuery('eq(a,1)'), null, true],

            // PATCH, но обычный list
            ['PATCH', [1, 2], new RqlQuery('eq(a,1)'), null, false],

            // PATCH, body пустой
            ['PATCH', [], new RqlQuery('eq(a,1)'), null, false],

            // PATCH, rqlQuery пустой
            ['PATCH', ['foo' => 11], new RqlQuery(''), null, false],

            // PATCH, primaryKeyValue задан
            ['PATCH', ['foo' => 11], new RqlQuery('eq(a,1)'), 123, false],

            // Body ассоц массив, но method не PATCH
            ['PUT', ['foo' => 11], new RqlQuery('eq(a,1)'), null, false],
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
     * @return QueriedUpdateHandler
     */
    protected function createHandler(): QueriedUpdateHandler
    {
        $dataStore = $this->createDataStore();
        return new QueriedUpdateHandler($dataStore);
    }

    public function createDataStore(): DataStoresInterface
    {
        $memory = new Memory(['a', 'b', 'c', 'foo']);
        for ($i = 0; $i < 4; $i++) {
            $memory->create(['a' => $i, 'b' => $i, 'c' => $i, 'foo' => $i], true);
        }
        return $memory;
    }

    public function testHandleSuccess()
    {
        $fields = ['archived' => true];
        $rqlQuery = new RqlQuery('eq(status,new)');

        /** @var DataStoresInterface|MockObject $dataStore */
        $dataStore = $this->createMock(HttpClient::class);

        $dataStore->expects($this->once())
            ->method('queriedUpdate')
            ->with($fields, $rqlQuery)
            ->willReturn([1, 2]);

        $handler = new QueriedUpdateHandler($dataStore);

        $request = (new ServerRequest())
            ->withMethod('PATCH')
            ->withParsedBody($fields)
            ->withAttribute('rqlQueryObject', $rqlQuery)
            ->withAttribute('primaryKeyValue', null);

        $response = $handler->process($request, $this->getNullHandler());
        $this->assertNotNull($response);
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
