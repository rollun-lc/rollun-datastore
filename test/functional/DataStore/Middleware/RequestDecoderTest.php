<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\functional\DataStore\Middleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_Error_Deprecated;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Http\Message\ServerRequestInterface;
use rollun\datastore\Middleware\RequestDecoder;
use rollun\datastore\Middleware\RestException;
use rollun\datastore\Rql\Node\AggregateSelectNode;
use rollun\datastore\Rql\Node\GroupbyNode;
use rollun\datastore\Rql\RqlQuery;
use Xiag\Rql\Parser\Node\LimitNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\EqNode;
use Xiag\Rql\Parser\Node\SortNode;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Stream;
use Zend\Diactoros\Uri;

class RequestDecoderTest extends TestCase
{

    /**
     * @dataProvider dataProviderForOverwriteMode
     * @dataProvider dataProviderParseQuery
     * @dataProvider dataProviderParseBody
     * @dataProvider dataProviderRangeHeader
     * @param callable $getRequest
     * @param callable $assertion
     */
    public function testProcess(callable $getRequest, callable $assertion)
    {
        $request = $getRequest();

        /** @var PHPUnit_Framework_MockObject_MockObject|DelegateInterface $mockDelegate */
        $mockDelegate = $this->getMockBuilder(DelegateInterface::class)
            ->getMock();
        $mockDelegate->expects($this->once())
            ->method('process')
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

        /** @var DelegateInterface $delegateMock */
        $delegateMock = $this->getMockBuilder(DelegateInterface::class)
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
                function (ServerRequestInterface $request) {
                    return $request->getAttribute('overwriteMode') == false;
                },
            ],
            [
                function () {
                    $request = new ServerRequest();
                    $request = $request->withHeader('If-Match', '*');

                    return $request;
                },
                function (ServerRequestInterface $request) {
                    return $request->getAttribute('overwriteMode') === true;
                },
            ],
            [
                function () {
                    $request = new ServerRequest();
                    $request = $request->withHeader('If-Match', 'foo');

                    return $request;
                },
                function (ServerRequestInterface $request) {
                    return $request->getAttribute('overwriteMode') === false;
                },
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
                function (ServerRequestInterface $request) {
                    return $request->getParsedBody() === ['a' => 'b'];
                },
            ],
            [
                function () {
                    $request = new ServerRequest();
                    $request = $request->withHeader('Content-Type', 'text/plain');

                    return $request;
                },
                function (ServerRequestInterface $request) {
                    return $request->getParsedBody() === null;
                },
            ],
            [
                function () {
                    $request = new ServerRequest();
                    $request = $request->withHeader('Content-Type', 'text/html');

                    return $request;
                },
                function (ServerRequestInterface $request) {
                    return $request->getParsedBody() === null;
                },
            ],
            [
                function () {
                    $request = new ServerRequest();
                    $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

                    return $request;
                },
                function (ServerRequestInterface $request) {
                    return $request->getParsedBody() === null;
                },
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
                function (ServerRequestInterface $request) {
                    return $request->getAttribute('Limit') === null;
                },
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
                function (ServerRequestInterface $request) {
                    return $request->getAttribute('Limit') === null;
                },
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
