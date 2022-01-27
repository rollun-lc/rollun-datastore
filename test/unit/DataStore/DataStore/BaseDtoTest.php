<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\unit\DataStore\DataStore;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use rollun\datastore\DataStore\BaseDto;
use rollun\datastore\DataStore\Type\TypeInt;
use rollun\datastore\DataStore\Type\TypeString;

class BaseDtoTest extends TestCase
{
    public function testCreateFromArraySuccess()
    {
        $id = new TypeInt('1');
        $name = new TypeString('name');
        $object = new TestDto([
            'id' => $id,
            'name' => $name
        ]);

        //$this->assertAttributeEquals($id, 'id', $object);
        //$this->assertAttributeEquals($name, 'name', $object);

        $property = new \ReflectionProperty($object, 'id');
        $property->setAccessible(true);
        $this->assertEquals($id, $property->getValue($object));

        $property = new \ReflectionProperty($object, 'name');
        $property->setAccessible(true);
        $this->assertEquals($name, $property->getValue($object));
    }

    public function testCreateFromArrayFail()
    {
        $this->expectException(InvalidArgumentException::class);
        $id = new TypeInt('1');
        $name = new TypeString('name');
        $object = new TestDto([
            'foo' => $id,
            'boo' => $name
        ]);

        //$this->assertAttributeEquals($id, 'id', $object);
        //$this->assertAttributeEquals($name, 'name', $object);

        $property = new \ReflectionProperty($object, 'id');
        $property->setAccessible(true);
        $this->assertEquals($id, $property->getValue($object));

        $property = new \ReflectionProperty($object, 'name');
        $property->setAccessible(true);
        $this->assertEquals($name, $property->getValue($object));

    }
}

class TestDto extends BaseDto {
    /** @var TypeInt  */
    protected $id;

    /** @var TypeString  */
    protected $name;
}
