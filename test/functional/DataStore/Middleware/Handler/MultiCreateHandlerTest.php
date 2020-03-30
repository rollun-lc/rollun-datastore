<?php
declare(strict_types=1);

namespace rollun\test\functional\DataStore\Middleware\Handler;

use rollun\datastore\DataStore\Memory;
use rollun\datastore\Middleware\Handler\MultiCreateHandler;
use rollun\datastore\Rql\RqlQuery;
use Zend\Diactoros\ServerRequest;

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
            ['POST', [["id" => 1, "name" => 1],["id" => 1, "name" => 1],["id" => 1, "name" => 1],["id" => 1, "name" => 1]], true]
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
}
