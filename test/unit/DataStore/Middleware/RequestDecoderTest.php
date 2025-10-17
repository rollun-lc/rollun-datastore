<?php
declare(strict_types=1);

namespace rollun\test\unit\DataStore\Middleware;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use rollun\datastore\Middleware\RequestDecoder;
use rollun\datastore\Middleware\RestException;
use rollun\utils\Json\Serializer;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Stream;
use Laminas\Diactoros\Response;
use Xiag\Rql\Parser\Query;

final class RequestDecoderTest extends TestCase
{
    /**
     * @dataProvider contentTypeCases
     */
    public function testParseBodyJson(string $contentType, string $body, mixed $expectedParsed): void
    {
        // Arrange
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $body);
        rewind($stream);

        $request = (new ServerRequest())
            ->withHeader('Content-Type', $contentType)
            ->withBody(new Stream($stream));

        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturnCallback(
            fn($req) => new Response\EmptyResponse(200, [], Serializer::jsonSerialize($req->getParsedBody()))
        );

        $middleware = new RequestDecoder();

        // Act
        $response = $middleware->process($request, $handler);
        $parsed = Serializer::jsonUnserialize((string) $response->getBody());

        // Assert
        $this->assertSame($expectedParsed, $parsed);
    }

    public function contentTypeCases(): iterable
    {
        yield 'application/json' => ['application/json', '{"foo":"bar"}', ['foo' => 'bar']];
        yield 'text/html' => ['text/html', '{"foo":"bar"}', null];
    }

    public function testIfMatchHeaderSetsOverwriteMode(): void
    {
        $request = (new ServerRequest())->withHeader('If-Match', '*');

        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturnCallback(
            fn($req) => new Response\EmptyResponse(200, [], json_encode($req->getAttribute('overwriteMode')))
        );

        $middleware = new RequestDecoder();
        $response = $middleware->process($request, $handler);

        $this->assertSame('true', (string) $response->getBody());
    }

    public function testWithContentRangeSetsFlag(): void
    {
        $request = (new ServerRequest())->withHeader('With-Content-Range', '*');

        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturnCallback(
            fn($req) => new Response\EmptyResponse(200, [], json_encode($req->getAttribute('withContentRange')))
        );

        $middleware = new RequestDecoder();
        $response = $middleware->process($request, $handler);

        $this->assertSame('true', (string) $response->getBody());
    }

    public function testRqlQueryParsesAndSetsAttribute(): void
    {
        // Arrange: создаём запрос с RQL-строкой
        $request = (new ServerRequest())
            ->withQueryParams(['query' => 'eq(id,1)&XDEBUG_SESSION_START=PHPSTORM']);

        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturnCallback(
            fn($req) => new Response\EmptyResponse(200, [], get_class($req->getAttribute('rqlQueryObject')))
        );

        $middleware = new RequestDecoder();

        // Act
        $response = $middleware->process($request, $handler);

        // Assert
        $this->assertSame(Query::class, (string) $response->getBody());
    }

    public function testRangeHeaderSetsLimitAttribute(): void
    {
        // Arrange
        $request = (new ServerRequest())->withHeader('Range', 'items=10-29');

        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturnCallback(
            fn($req) => new Response\EmptyResponse(200, [], json_encode($req->getAttribute('Limit')))
        );

        $middleware = new RequestDecoder();
        $response = $middleware->process($request, $handler);

        $this->assertSame(['offset' => 10, 'limit' => 20], json_decode((string)$response->getBody(), true));
    }

    public function testInvalidContentTypeThrowsException(): void
    {
        $this->expectException(RestException::class);
        $this->expectExceptionMessageMatches('/Unsupported Content-Type/');

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, '<xml></xml>');
        rewind($stream);

        $request = (new ServerRequest())
            ->withHeader('Content-Type', 'application/xml')
            ->withBody(new Stream($stream));

        $middleware = new RequestDecoder();
        $middleware->process($request, $this->createStub(RequestHandlerInterface::class));
    }
}
