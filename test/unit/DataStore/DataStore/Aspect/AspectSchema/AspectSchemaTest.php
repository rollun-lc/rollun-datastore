<?php

declare(strict_types=1);

namespace rollun\test\unit\DataStore\DataStore\Aspect\AspectSchema;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use rollun\datastore\DataStore\Aspect\AspectSchema;
use rollun\datastore\DataStore\Entity\EntityFactory;
use rollun\datastore\DataStore\Formatter\StringFormatter;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use rollun\datastore\DataStore\Query\QueryAdapter;
use rollun\datastore\DataStore\Scheme\FieldInfo;
use rollun\datastore\DataStore\Scheme\MethodGetter;
use rollun\datastore\DataStore\Scheme\PluginManagerTypeFactory;
use rollun\datastore\DataStore\Scheme\Scheme;
use rollun\datastore\DataStore\Type\TypeBoolean;
use rollun\datastore\DataStore\Type\TypeFloat;
use rollun\datastore\DataStore\Type\TypeInt;
use rollun\datastore\DataStore\Type\TypeString;

class AspectSchemaTest extends TestCase
{
    /**
     * @var MockObject|null
     */
    private $dataStoreMock;

    /**
     * @var MockObject|null
     */
    private $entityFactoryMock;

    /**
     * @var MockObject|null
     */
    private $queryAdapterMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dataStoreMock = null;
        $this->entityFactoryMock = null;
        $this->queryAdapterMock = null;
    }

    public function createDataProvider(): array
    {
        return [
            'Int value' => [
                new Scheme([
                    'value' => new FieldInfo(
                        $this->getIntTypeFactory(),
                        new StringFormatter(),
                        new MethodGetter('getValue'),
                        false
                    ),
                ]),
                new IntValueObject($this->getRandomInt()),
            ],
            'Float value' => [
                new Scheme([
                    'value' => new FieldInfo(
                        $this->getFloatTypeFactory(),
                        new StringFormatter(),
                        new MethodGetter('getValue'),
                        false
                    ),
                ]),
                new FloatValueObject($this->getRandomFloat())
            ],
            'String value' => [
                new Scheme([
                    'value' => new FieldInfo(
                        $this->getStringTypeFactory(),
                        new StringFormatter(),
                        new MethodGetter('getValue'),
                        false
                    ),
                ]),
                new StringValueObject($this->getRandomString())
            ],
            'Bool value' => [
                new Scheme([
                    'value' => new FieldInfo(
                        $this->getBoolTypeFactory(),
                        new StringFormatter(),
                        new MethodGetter('getValue'),
                        false
                    ),
                ]),
                new BoolValueObject($value = $this->getRandomBool())
            ],
        ];
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testCreateConversionToRecord(Scheme $scheme, $object)
    {
        $this->getDataStoreMock()
            ->expects($this->once())
            ->method('create')
            ->with($this->getExpectedRecord($scheme, $object))
            ->willReturnArgument(0);

        $this->createAspectAnyObject($scheme)->create($object);
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testCreateConversionFromRecord(Scheme $scheme, $object)
    {
        $this->getDataStoreMock()->method('create')->willReturnArgument(0);

        $this->getEntityFactoryMock()
            ->expects($this->once())
            ->method('fromRecord')
            ->with($this->getExpectedTypedRecord($scheme, $object))
            ->willReturn($object);

        $result = $this->createAspectAnyObject($scheme)->create($object);

        self::assertSame($object, $result);
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testRead(Scheme $scheme, $object)
    {
        $id = $this->getRandomInt();
        $this->getDataStoreMock()
            ->expects($this->once())
            ->method('read')
            ->with($id)
            ->willReturn($this->getExpectedRecord($scheme, $object));

        $this->getEntityFactoryMock()
            ->expects($this->once())
            ->method('fromRecord')
            ->with($this->getExpectedTypedRecord($scheme, $object))
            ->willReturn($object);

        $result = $this->createAspectAnyObject($scheme)->read($id);

        self::assertSame($object, $result);
    }

    private function getExpectedRecord(Scheme $scheme, $object): array
    {
        $record = [];
        foreach ($scheme->toArray() as $fieldName => $fieldInfo) {
            $record[$fieldName] = $fieldInfo->getFormatter()->format($fieldInfo->getGetter()->get($object));
        }
        return $record;
    }

    private function getExpectedTypedRecord(Scheme $scheme, $object): array
    {
        $result = [];
        foreach ($scheme->toArray() as $fieldName => $fieldInfo) {
            $result[$fieldName] = $fieldInfo->getGetter()->get($object);
        }
        return $result;
    }

    private function createAspectAnyObject(Scheme $scheme): AspectSchema
    {
        return new AspectSchema(
            $this->getDataStoreMock(),
            $scheme,
            $this->getEntityFactoryMock(),
            $this->getQueryAdapterMock()
        );
    }

    /**
     * @return MockObject&DataStoresInterface
     */
    private function getDataStoreMock(): MockObject
    {
        if ($this->dataStoreMock === null) {
            $this->dataStoreMock = $this->createMock(DataStoresInterface::class);
        }
        return $this->dataStoreMock;
    }

    /**
     * @return MockObject&EntityFactory
     */
    private function getEntityFactoryMock(): MockObject
    {
        if ($this->entityFactoryMock === null) {
            $this->entityFactoryMock = $this->createMock(EntityFactory::class);
        }
        return $this->entityFactoryMock;
    }

    /**
     * @return MockObject&QueryAdapter
     */
    private function getQueryAdapterMock(): MockObject
    {
        if ($this->queryAdapterMock === null) {
            $this->queryAdapterMock = $this->createMock(QueryAdapter::class);
        }
        return $this->queryAdapterMock;
    }

    private function getIntTypeFactory(): PluginManagerTypeFactory
    {
        return new PluginManagerTypeFactory(TypeInt::class);
    }

    private function getFloatTypeFactory(): PluginManagerTypeFactory
    {
        return new PluginManagerTypeFactory(TypeFloat::class);
    }

    private function getStringTypeFactory(): PluginManagerTypeFactory
    {
        return new PluginManagerTypeFactory(TypeString::class);
    }

    private function getBoolTypeFactory(): PluginManagerTypeFactory
    {
        return new PluginManagerTypeFactory(TypeBoolean::class);
    }

    private function getRandomInt(): int
    {
        return rand();
    }

    private function getRandomFloat(): float
    {
        return $this->getRandomInt() / 100;
    }

    private function getRandomString(): string
    {
        return uniqid();
    }

    private function getRandomBool(): bool
    {
        return (bool)rand(0, 1);
    }
}
