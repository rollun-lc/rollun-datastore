<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\unit\DataStore\DataStore\Type;

use PHPUnit\Framework\TestCase;
use rollun\datastore\DataStore\Type\TypeException;
use rollun\datastore\DataStore\Type\TypeInt;

class TypeIntTest extends TestCase
{
    protected function createObject($value)
    {
        return new TypeInt($value);
    }

    public function testGetTypeName()
    {
        $this->assertEquals(TypeInt::getTypeName(), 'integer');
    }

    public function testToTypeValueSuccess()
    {
        $this->assertSame(1, $this->createObject(1)->toTypeValue());
        $this->assertSame(1, $this->createObject('1')->toTypeValue());
        $this->assertSame(1, $this->createObject(1.001)->toTypeValue());
        $this->assertSame(1, $this->createObject(['a', 'b'])->toTypeValue());
        $this->assertSame(1, $this->createObject(true)->toTypeValue());
        $this->assertSame(0, $this->createObject(false)->toTypeValue());
        $this->assertSame(0, $this->createObject(0.999)->toTypeValue());
        $this->assertSame(0, $this->createObject([])->toTypeValue());
        $this->assertSame(0, $this->createObject(null)->toTypeValue());

        $resource = fopen('http://google.com', 'r');
        $this->assertSame(intval($resource), $this->createObject($resource)->toTypeValue());
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
        $this->createObject(function (): void {
        })->toTypeValue();
    }
}
