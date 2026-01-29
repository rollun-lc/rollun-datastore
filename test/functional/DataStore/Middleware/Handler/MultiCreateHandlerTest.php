<?php

declare(strict_types=1);

namespace rollun\test\functional\DataStore\Middleware\Handler;

use Psr\Http\Server\RequestHandlerInterface;
use rollun\datastore\DataStore\Memory;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use rollun\datastore\Middleware\Handler\MultiCreateHandler;
use rollun\datastore\Rql\RqlQuery;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Response;

/**
 * Class MultiCreateHandlerTest
 *
 * @author    Roman Ratsun <r.ratsun.rollun@gmail.com>
 *
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license   LICENSE.md New BSD License
 */
class MultiCreateHandlerTest extends BaseHandlerTest
{
    /**
     * @return array
     */
    public function canHandleProvider()
    {
        return [
            ['POST', [1, 2], false],
            ['POST', ["id" => 1, "name" => 1], false],
            ['PUT', [["id" => 1, "name" => 1]], false],
            ['POST', [["id" => 1, "name" => 1],1], false],
            ['POST', [["id" => 1, "name" => 1]], true],
            ['POST', [["id" => 1, "name" => 1],["id" => 1, "name" => 1],["id" => 1, "name" => 1],["id" => 1, "name" => 1]], true],
        ];
    }

    /**
     * @param string $method
     * @param array  $data
     * @param bool   $expected
     *
     * @dataProvider canHandleProvider
     */
    public function testCanHandle(string $method, array $data, bool $expected)
    {
        $this->assertEquals($expected, $this->createHandler()->canHandle($this->createRequest($method, $data)));
    }

    /**
     * @return MultiCreateHandler
     */
    protected function createHandler(): MultiCreateHandler
    {
        return new MultiCreateHandler(new Memory());
    }

    /**
     * @param string $method
     * @param array  $data
     *
     * @return ServerRequest
     */
    protected function createRequest(string $method, array $data): ServerRequest
    {
        return (new ServerRequest())
            ->withMethod($method)
            ->withAttribute('rqlQueryObject', new RqlQuery(''))
            ->withParsedBody($data);
    }

    public function testHandleFallbackSoftWhenMultiCreateNotSupported()
    {
        $prev = getenv('DATASTORE_MULTI_POLICY');
        putenv('DATASTORE_MULTI_POLICY=soft');

        try {
            $records = [
                ['id' => 1, 'name' => 'a'],
                ['id' => 2, 'name' => 'b'],
            ];

            $dataStore = $this->createMock(DataStoresInterface::class);
            $dataStore->expects($this->exactly(2))
                ->method('create')
                ->withConsecutive([$records[0]], [$records[1]])
                ->willReturnOnConsecutiveCalls(['id' => 1], ['id' => 2]);
            $dataStore->method('getIdentifier')->willReturn('id');

            $handler = new MultiCreateHandler($dataStore);
            $request = (new ServerRequest())
                ->withMethod('POST')
                ->withParsedBody($records)
                ->withAttribute('rqlQueryObject', new RqlQuery(''));

            $response = $handler->process($request, $this->getNullHandler());

            $this->assertSame(201, $response->getStatusCode());
            $body = json_decode((string) $response->getBody(), true);
            $this->assertSame([1, 2], $body);
        } finally {
            if ($prev === false) {
                putenv('DATASTORE_MULTI_POLICY');
            } else {
                putenv('DATASTORE_MULTI_POLICY=' . $prev);
            }
        }
    }

    public function testHandleFallbackStrictWhenMultiCreateNotSupported()
    {
        $prev = getenv('DATASTORE_MULTI_POLICY');
        putenv('DATASTORE_MULTI_POLICY=strict');

        try {
            $records = [
                ['id' => 1, 'name' => 'a'],
            ];

            $dataStore = $this->createMock(DataStoresInterface::class);
            $dataStore->expects($this->never())->method('create');

            $handler = new MultiCreateHandler($dataStore);
            $request = (new ServerRequest())
                ->withMethod('POST')
                ->withParsedBody($records)
                ->withAttribute('rqlQueryObject', new RqlQuery(''));

            $this->expectException(DataStoreException::class);
            $this->expectExceptionMessage(
                'Multi create is not supported by this datastore. ' .
                'Please implement the multiCreate() method or use individual create() calls.'
            );

            $handler->process($request, $this->getNullHandler());
        } finally {
            if ($prev === false) {
                putenv('DATASTORE_MULTI_POLICY');
            } else {
                putenv('DATASTORE_MULTI_POLICY=' . $prev);
            }
        }
    }

    private function getNullHandler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
            {
                return new Response();
            }
        };
    }
}
