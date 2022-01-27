<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\unit\DataStore\DataStore\Type;

use PHPUnit\Framework\TestCase;
use rollun\datastore\DataStore\Type\TypeChar;
use rollun\datastore\DataStore\Type\TypeException;

class TypeCharTest extends TestCase
{
    protected function createObject($value)
    {
        return new TypeChar($value);
    }

    public function testGetTypeName()
    {
        $this->assertEquals(TypeChar::getTypeName(), 'char');
    }

    public function testToTypeValueSuccess()
    {
        $this->assertSame(49, ord($this->createObject(1)->toTypeValue()));
        $this->assertSame(49, ord($this->createObject('1')->toTypeValue()));
        $this->assertSame(49, ord($this->createObject(1.001)->toTypeValue()));
        $this->assertSame(49, ord($this->createObject(true)->toTypeValue()));
        $this->assertSame(48, ord($this->createObject(0.999)->toTypeValue()));
        $this->assertSame(0, ord($this->createObject('')->toTypeValue()));
        $this->assertSame(0, ord($this->createObject(false)->toTypeValue()));
        $this->assertSame(0, ord($this->createObject(null)->toTypeValue()));
        $this->assertSame(97, ord($this->createObject('a')->toTypeValue()));
        $this->assertSame(91, ord($this->createObject('[')->toTypeValue()));
    }

    public function testToTypeValueFailWithObject()
    {
        $this->expectException(TypeException::class);
        $this->createObject(new class {})->toTypeValue();
    }

    public function testToTypeValueFailWithCallable()
    {
        $this->expectException(TypeException::class);
        $this->createObject(function () {})->toTypeValue();
    }

    public function testToTypeValueFailWithArray()
    {
        $this->expectException(TypeException::class);
        $this->createObject([])->toTypeValue();
    }

    public function testToTypeValueFailWithResource()
    {
        $this->expectException(TypeException::class);
        $resource = fopen('http://google.com', 'r');
        $this->assertSame(true, $this->createObject($resource)->toTypeValue());
    }

    public function testToTypeValueFailWithWrongChar()
    {
        $this->expectException(TypeException::class);
        $this->assertSame(91, ord($this->createObject('☺')->toTypeValue()));
    }
}
