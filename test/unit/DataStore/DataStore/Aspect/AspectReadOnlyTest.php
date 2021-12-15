<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\unit\DataStore\DataStore\Aspect;

use PHPUnit\Framework\TestCase;
use rollun\datastore\DataStore\Aspect\AspectReadOnly;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\Interfaces\ReadInterface;
use rollun\datastore\DataStore\Memory;
use Xiag\Rql\Parser\Query;

class AspectReadOnlyTest extends TestCase
{
    public function disallowedMethods(): array
    {
        $excluded = [
            '__construct',
        ];

        $methods = get_class_methods(AspectReadOnly::class);

        $methods = array_filter($methods, function ($method) use ($excluded) {
            return !in_array($method, $excluded) && !method_exists(ReadInterface::class, $method);
        });

        return array_map(function ($method) {
            return [$method];
        }, $methods);
    }

    /**
     * @dataProvider disallowedMethods
     */
    public function testOnlyReadMethodsAreAvailable($method)
    {
        $params = [
            'default' => [[]],
            'delete' => [1],
            'queriedUpdate' => [[], new Query()],
            'queriedDelete' => [new Query()],
        ];

        $datastore = new Memory();

        $aspect = new AspectReadOnly($datastore);

        $this->expectException(DataStoreException::class);

        $aspect->$method(...($params[$method] ?? $params['default']));
    }
}
