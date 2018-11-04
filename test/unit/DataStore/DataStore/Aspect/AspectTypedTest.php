<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace test\unit\DataStore\DataStore\Aspect;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use rollun\datastore\DataStore\Aspect\AspectTyped;
use rollun\datastore\DataStore\BaseDto;
use rollun\datastore\DataStore\Formatter\FormatterInterface;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use rollun\datastore\DataStore\Type\TypeInterface;
use rollun\datastore\Rql\RqlQuery;

class AspectTypedTest extends TestCase
{
    public function testConstructSuccess()
    {
        $scheme = [
            'id' => [
                'type' => TypeInterface::class,
                'formatter' => FormatterInterface::class,
            ],
        ];
        $dtoClassName = BaseDto::class;

        /** @var PHPUnit_Framework_MockObject_MockObject|DataStoresInterface $dataStore */
        $dataStore = $this->getMockBuilder(DataStoresInterface::class)->getMock();

        $object = new AspectTyped($dataStore, $scheme, $dtoClassName);
        $this->assertAttributeEquals($scheme, 'scheme', $object);
        $this->assertAttributeEquals($dtoClassName, 'dtoClassName', $object);
        $this->assertAttributeEquals($dataStore, 'dataStore', $object);
    }

    public function testConstructFailSchemeWithInvalidType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid option 'type' in scheme");
        $scheme = [
            'id' => [
                'formatter' => FormatterInterface::class,
            ],
        ];
        $dtoClassName = BaseDto::class;

        /** @var PHPUnit_Framework_MockObject_MockObject|DataStoresInterface $dataStore */
        $dataStore = $this->getMockBuilder(DataStoresInterface::class)->getMock();
        new AspectTyped($dataStore, $scheme, $dtoClassName);
    }

    public function testConstructFailSchemeWithInvalidFormatter()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid option 'formatter' in scheme");
        $scheme = [
            'id' => [
                'type' => TypeInterface::class,
            ],
        ];
        $dtoClassName = BaseDto::class;

        /** @var PHPUnit_Framework_MockObject_MockObject|DataStoresInterface $dataStore */
        $dataStore = $this->getMockBuilder(DataStoresInterface::class)->getMock();
        new AspectTyped($dataStore, $scheme, $dtoClassName);
    }

    public function testConstructFailDtoClassName()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid value for 'dtoClassName' property");
        $scheme = [
            'id' => [
                'type' => TypeInterface::class,
                'formatter' => FormatterInterface::class,
            ],
        ];
        $dtoClassName = get_class(new class
        {
        });

        /** @var PHPUnit_Framework_MockObject_MockObject|DataStoresInterface $dataStore */
        $dataStore = $this->getMockBuilder(DataStoresInterface::class)->getMock();
        new AspectTyped($dataStore, $scheme, $dtoClassName);
    }

    public function testCreateAndCreate()
    {
        $scheme = [
            'id' => [
                'type' => TypeInt::class,
                'formatter' => StringFormatter::class,
            ],
            'name' => [
                'type' => TypeString::class,
                'formatter' => StringFormatter::class,
            ],
        ];

        /** @var PHPUnit_Framework_MockObject_MockObject|DataStoresInterface $dataStore */
        $dataStore = $this->getMockBuilder(DataStoresInterface::class)
            ->getMock();

        $dto = new UserDto(new TypeInt(1), new TypeString('name'));
        $dataStore
            ->method('create')
            ->with([
                'id' => '1',
                'name' => 'name',
            ]);

        $dataStore
            ->method('update')
            ->with([
                'id' => '1',
                'name' => 'name',
            ]);

        $object = new AspectTyped($dataStore, $scheme, UserDto::class);
        $object->create($dto);
        $object->update($dto);
    }

    public function testQuery()
    {
        $scheme = [
            'id' => [
                'type' => TypeInt::class,
                'formatter' => StringFormatter::class,
            ],
            'name' => [
                'type' => TypeString::class,
                'formatter' => StringFormatter::class,
            ],
        ];

        /** @var PHPUnit_Framework_MockObject_MockObject|DataStoresInterface $dataStore */
        $dataStore = $this->getMockBuilder(DataStoresInterface::class)
            ->getMock();

        $dataStore
            ->method('query')
            ->will($this->returnValue([
                [
                    'id' => '1',
                    'name' => 'name1',
                ],
                [
                    'id' => '2',
                    'name' => 'name2',
                ],
            ]));

        $object = new AspectTyped($dataStore, $scheme, UserDto::class);
        $this->assertEquals([
            new UserDto(new TypeInt(1), new TypeString('name1')),
            new UserDto(new TypeInt(2), new TypeString('name2')),
        ], $object->query(new RqlQuery()));
    }

    public function testGetScheme()
    {
        $scheme = [
            'id' => [
                'type' => TypeInt::class,
                'formatter' => StringFormatter::class,
            ],
            'name' => [
                'type' => TypeString::class,
                'formatter' => StringFormatter::class,
            ],
        ];

        /** @var PHPUnit_Framework_MockObject_MockObject|DataStoresInterface $dataStore */
        $dataStore = $this->getMockBuilder(DataStoresInterface::class)
            ->getMock();
        $object = new AspectTyped($dataStore, $scheme, UserDto::class);
        $this->assertEquals($object->getScheme(), [
            'id' => [
                'type' => TypeInt::getTypeName(),
            ],
            'name' => [
                'type' => TypeString::getTypeName(),
            ],
        ]);
    }
}

class UserDto extends BaseDto
{
    protected $id;

    protected $name;

    public function __construct(TypeInt $id, TypeString $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    public function getId()
    {
        return $this->id->toTypeValue();
    }

    public function getName()
    {
        return $this->name->toTypeValue();
    }
}

;

class TypeInt implements TypeInterface
{
    protected $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function toTypeValue()
    {
        return (int)$this->value;
    }

    public static function getTypeName()
    {
        return 'int';
    }
}

class TypeString implements TypeInterface
{
    protected $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function toTypeValue()
    {
        return (string)$this->value;
    }

    public static function getTypeName()
    {
        return 'string';
    }
}

class StringFormatter implements FormatterInterface
{
    public function format($value)
    {
        return (string)$value;
    }
}
