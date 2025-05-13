<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\unit\DataStore\DataStore\Type;

use PHPUnit\Framework\TestCase;
use rollun\datastore\DataStore\Type\TypeException;
use rollun\datastore\DataStore\Type\TypeFloat;

class TypeFloatTest extends TestCase
{
    protected function createObject($value)
    {
        return new TypeFloat($value);
    }

    public function testGetTypeName()
    {
        $this->assertEquals(TypeFloat::getTypeName(), 'float');
    }

    public function testToTypeValueSuccess()
    {
        $this->assertSame(1.0, $this->createObject(1)->toTypeValue());
        $this->assertSame(1.0, $this->createObject('1')->toTypeValue());
        $this->assertSame(1.0, $this->createObject(1.0)->toTypeValue());
        $this->assertSame(1.0, $this->createObject(['a', 'b'])->toTypeValue());
        $this->assertSame(1.0, $this->createObject(true)->toTypeValue());
        $this->assertSame(0.0, $this->createObject(false)->toTypeValue());
        $this->assertSame(0.0, $this->createObject([])->toTypeValue());
        $this->assertSame(0.0, $this->createObject(null)->toTypeValue());

        $resource = fopen('http://google.com', 'r');
        $this->assertSame(floatval($resource), $this->createObject($resource)->toTypeValue());
    }

    public function testToTypeValueFailWithObject()
    {
        $this->expectException(TypeException::class);
        $this->createObject(new class {})->toTypeValue();
    }

    public function testToTypeValueFailWithCallable()
    {
        $this->expectException(TypeException::class);
        $this->createObject(function (): void {})->toTypeValue();
    }
}
