<?php

declare(strict_types=1);

namespace rollun\test\intagration\DataStore\DataStoreRest;

use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Response\JsonResponse;
use PHPUnit\Framework\TestCase;
use rollun\datastore\DataStore\Memory;
use rollun\datastore\Middleware\Handler\MultiUpdateHandler;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class MultiUpdateHandlerTest extends TestCase
{
    public function testDispatchesMultiUpdateRequest(): void
    {
        $dataStore = new Memory(['id', 'name']);
        $dataStore->create(['id' => 1, 'name' => 'foo']);
        $dataStore->create(['id' => 2, 'name' => 'bar']);

        $request = (new ServerRequest([], [], '/api/datastore/test', 'PATCH'))
            ->withHeader('X-DataStore-Operation', 'multi-update')
            ->withParsedBody([
                ['id' => 1, 'name' => 'updated-foo'],
                ['id' => 2, 'name' => 'updated-bar'],
            ]);

        $handler = new MultiUpdateHandler($dataStore);

        self::assertTrue($handler->canHandle($request));

        $response = $handler->process($request, new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new JsonResponse(['fallback']);
            }
        });
        self::assertSame(200, $response->getStatusCode());

        $body = $response->getBody();
        $body->rewind();
        $payload = json_decode($body->getContents(), true);
        sort($payload);
        self::assertSame([1, 2], $payload);
    }
}
