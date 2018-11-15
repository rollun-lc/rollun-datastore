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
        $object = TestDto::createFromArray([
            'id' => $id,
            'name' => $name
        ]);

        $this->assertAttributeEquals($id, 'id', $object);
        $this->assertAttributeEquals($name, 'name', $object);
    }

    public function testCreateFromArrayFail()
    {
        $this->expectException(InvalidArgumentException::class);
        $id = new TypeInt('1');
        $name = new TypeString('name');
        $object = TestDto::createFromArray([
            'foo' => $id,
            'boo' => $name
        ]);

        $this->assertAttributeEquals($id, 'id', $object);
        $this->assertAttributeEquals($name, 'name', $object);
    }
}

class TestDto extends BaseDto {
    /** @var TypeInt  */
    protected $id;

    /** @var TypeString  */
    protected $name;

    public function __construct(TypeInt $id, TypeString $name)
    {
        $this->id = $id;
        $this->name = $name;
    }
}
