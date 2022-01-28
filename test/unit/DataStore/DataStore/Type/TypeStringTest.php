<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\unit\DataStore\DataStore\Type;

use PHPUnit\Framework\TestCase;
use rollun\datastore\DataStore\Type\TypeException;
use rollun\datastore\DataStore\Type\TypeString;

class TypeStringTest extends TestCase
{
    protected function createObject($value)
    {
        return new TypeString($value);
    }

    public function testGetTypeName()
    {
        $this->assertEquals(TypeString::getTypeName(), 'string');
    }

    public function testToTypeValueSuccess()
    {
        $this->assertSame('1', $this->createObject(1)->toTypeValue());
        $this->assertSame('1', $this->createObject('1')->toTypeValue());
        $this->assertSame('abcd', $this->createObject('abcd')->toTypeValue());
        $this->assertSame('1', $this->createObject(1.0)->toTypeValue());
        $this->assertSame('0.99', $this->createObject(0.99)->toTypeValue());
        $this->assertSame('1', $this->createObject(true)->toTypeValue());
        $this->assertSame('', $this->createObject(false)->toTypeValue());
        $this->assertSame('', $this->createObject(null)->toTypeValue());
        $this->assertSame('[]', $this->createObject([])->toTypeValue());
    }

    public function testToTypeValueFailWithObject()
    {
        $this->expectException(TypeException::class);
        $this->createObject(new class {
        })->toTypeValue();
    }

    public function testToTypeValueFailWithCallable()
    {
        $this->expectException(TypeException::class);
        $this->createObject(function () {
        })->toTypeValue();
    }

    public function testToTypeValueFailWithResource()
    {
        $this->expectException(TypeException::class);
        $resource = fopen('http://google.com', 'r');
        $this->assertSame(true, $this->createObject($resource)->toTypeValue());
    }
}
