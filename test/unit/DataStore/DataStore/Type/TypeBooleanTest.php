<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\unit\DataStore\DataStore\Type;

use PHPUnit\Framework\TestCase;
use rollun\datastore\DataStore\Type\TypeBoolean;

class TypeBooleanTest extends TestCase
{
    protected function createObject($value)
    {
        return new TypeBoolean($value);
    }

    public function testGetTypeName()
    {
        $this->assertEquals(TypeBoolean::getTypeName(), 'boolean');
    }

    public function testToTypeValueSuccess()
    {
        $this->assertSame(true, $this->createObject(1)->toTypeValue());
        $this->assertSame(true, $this->createObject('1')->toTypeValue());
        $this->assertSame(true, $this->createObject(1.001)->toTypeValue());
        $this->assertSame(true, $this->createObject(['a', 'b'])->toTypeValue());
        $this->assertSame(true, $this->createObject(true)->toTypeValue());
        $this->assertSame(true, $this->createObject(0.999)->toTypeValue());
        $this->assertSame(true, $this->createObject(new class {
        })->toTypeValue());
        $this->assertSame(true, $this->createObject(function (): void {
        })->toTypeValue());
        $this->assertSame(false, $this->createObject(false)->toTypeValue());
        $this->assertSame(false, $this->createObject([])->toTypeValue());
        $this->assertSame(false, $this->createObject(null)->toTypeValue());

        $resource = fopen('http://google.com', 'r');
        $this->assertSame(true, $this->createObject($resource)->toTypeValue());
    }
}
