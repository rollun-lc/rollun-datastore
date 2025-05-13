<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\functional\DataStore\Middleware;

use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use rollun\datastore\Middleware\RequestDecoder;
use rollun\datastore\Middleware\RestException;
use rollun\datastore\Rql\Node\AggregateSelectNode;
use rollun\datastore\Rql\Node\GroupbyNode;
use rollun\datastore\Rql\RqlQuery;
use Xiag\Rql\Parser\Node\LimitNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\EqNode;
use Xiag\Rql\Parser\Node\SortNode;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Stream;
use Laminas\Diactoros\Uri;

class RequestDecoderTest extends TestCase
{
    /**
     * @dataProvider dataProviderForOverwriteMode
     * @dataProvider dataProviderForContentRange
     * @dataProvider dataProviderParseQuery
     * @dataProvider dataProviderParseBody
     * @dataProvider dataProviderRangeHeader
     * @param callable $getRequest
     * @param callable $assertion
     */
    public function testProcess(callable $getRequest, callable $assertion)
    {
        $request = $getRequest();

        /** @var PHPUnit_Framework_MockObject_MockObject|RequestHandlerInterface $mockDelegate */
        $mockDelegate = $this->getMockBuilder(RequestHandlerInterface::class)
            ->getMock();
        $mockDelegate->expects($this->once())
            ->method('handle')
            ->with($this->callback($assertion));

        $object = new RequestDecoder();
        $object->process($request, $mockDelegate);
    }

    public function testProcessWithUnknownContentType()
    {
        $this->expectException(RestException::class);
        $this->expectExceptionMessage('Unknown Content-Type header - foo');
        $request = new ServerRequest();
        $request = $request->withHeader('Content-Type', 'foo');
        $object = new RequestDecoder();

        /** @var RequestHandlerInterface $delegateMock */
        $delegateMock = $this->getMockBuilder(RequestHandlerInterface::class)
            ->getMock();

        $object->process($request, $delegateMock);
    }

    public function dataProviderForOverwriteMode()
    {
        return [
            [
                function () {
                    $request = new ServerRequest();

                    return $request;
                },
                fn(ServerRequestInterface $request) => $request->getAttribute('overwriteMode') == false,
            ],
            [
                function () {
                    $request = new ServerRequest();
                    $request = $request->withHeader('If-Match', '*');

                    return $request;
                },
                fn(ServerRequestInterface $request) => $request->getAttribute('overwriteMode') === true,
            ],
            [
                function () {
                    $request = new ServerRequest();
                    $request = $request->withHeader('If-Match', 'foo');

                    return $request;
                },
                fn(ServerRequestInterface $request) => $request->getAttribute('overwriteMode') === false,
            ],
        ];
    }

    public function dataProviderForContentRange()
    {
        return [
            [
                function () {
                    $request = new ServerRequest();

                    return $request;
                },
                fn(ServerRequestInterface $request) => $request->getAttribute('withContentRange') == false,
            ],
            [
                function () {
                    $request = new ServerRequest();
                    $request = $request->withHeader('With-Content-Range', '*');

                    return $request;
                },
                fn(ServerRequestInterface $request) => $request->getAttribute('withContentRange') === true,
            ],
            [
                function () {
                    $request = new ServerRequest();
                    $request = $request->withHeader('With-Content-Range', 'foo');

                    return $request;
                },
                fn(ServerRequestInterface $request) => $request->getAttribute('withContentRange') === false,
            ],
        ];
    }

    public function dataProviderParseQuery()
    {
        return [
            [
                function () {
                    $request = new ServerRequest();
                    $request = $request->withUri(new Uri('/some/path?eq(a,1)'));

                    return $request;
                },
                function (ServerRequestInterface $request) {
                    $query = new RqlQuery();
                    $query->setQuery(new EqNode('a', '1'));

                    return $request->getAttribute('rqlQueryObject') == $query;
                },
            ],
            [
                function () {
                    $request = new ServerRequest();
                    $request = $request->withUri(
                        new Uri('/some/path?eq(a,1)&limit(5)&sort(a)&groupby(a)&select(a)')
                    );

                    return $request;
                },
                function (ServerRequestInterface $request) {
                    $query = new RqlQuery();
                    $query->setQuery(new EqNode('a', 1));
                    $query->setSort(new SortNode(['a' => 1]));
                    $query->setSelect(new AggregateSelectNode(['a']));
                    $query->setGroupBy(new GroupbyNode(['a']));
                    $query->setLimit(new LimitNode(5, 0));

                    return $request->getAttribute('rqlQueryObject') == $query;
                },
            ],
            [
                function () {
                    $request = new ServerRequest();
                    $request = $request->withUri(
                        new Uri('/some/path?eq(a,1)&XDEBUG_SESSION_START=21345')
                    );

                    return $request;
                },
                function (ServerRequestInterface $request) {
                    $query = new RqlQuery();
                    $query->setQuery(new EqNode('a', '1'));

                    return $request->getAttribute('rqlQueryObject') == $query;
                },
            ],
            [
                function () {
                    $request = new ServerRequest();
                    $request = $request->withUri(
                        new Uri('/some/path?')
                    );

                    return $request;
                },
                function (ServerRequestInterface $request) {
                    $query = new RqlQuery();

                    return $request->getAttribute('rqlQueryObject') == $query;
                },
            ],
        ];
    }

    public function dataProviderParseBody()
    {
        return [
            [
                function () {
                    $data = ['a' => 'b'];
                    $request = new ServerRequest();
                    $request = $request->withHeader('Content-Type', 'application/json');
                    $request = $request->withBody(
                        new Stream("data://text/plain;base64," . base64_encode(json_encode($data)), 'r')
                    );

                    return $request;
                },
                fn(ServerRequestInterface $request) => $request->getParsedBody() === ['a' => 'b'],
            ],
            [
                function () {
                    $request = new ServerRequest();
                    $request = $request->withHeader('Content-Type', 'text/plain');

                    return $request;
                },
                fn(ServerRequestInterface $request) => $request->getParsedBody() === null,
            ],
            [
                function () {
                    $request = new ServerRequest();
                    $request = $request->withHeader('Content-Type', 'text/html');

                    return $request;
                },
                fn(ServerRequestInterface $request) => $request->getParsedBody() === null,
            ],
            [
                function () {
                    $request = new ServerRequest();
                    $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

                    return $request;
                },
                fn(ServerRequestInterface $request) => $request->getParsedBody() === null,
            ],
        ];
    }

    public function dataProviderRangeHeader()
    {
        return [
            [
                function () {
                    $request = new ServerRequest();

                    return $request;
                },
                fn(ServerRequestInterface $request) => $request->getAttribute('Limit') === null,
            ],
            [
                function () {
                    $request = new ServerRequest();
                    $request = $request->withHeader('Range', 'items=22-22');

                    return $request;
                },
                function (ServerRequestInterface $request) {
                    $limit = $request->getAttribute('Limit');

                    return $limit['offset'] == 22
                        && $limit['limit'] == 22;
                },
            ],
            [
                function () {
                    $request = new ServerRequest();
                    $request = $request->withHeader('Range', 'items!=22\\');

                    return $request;
                },
                fn(ServerRequestInterface $request) => $request->getAttribute('Limit') === null,
            ],
            [
                function () {
                    $request = new ServerRequest();
                    $request = $request->withHeader('Range', 'items=2');

                    return $request;
                },
                function (ServerRequestInterface $request) {
                    $limit = $request->getAttribute('Limit');

                    return $limit['limit'] == 2;
                },
            ],
        ];
    }
}
