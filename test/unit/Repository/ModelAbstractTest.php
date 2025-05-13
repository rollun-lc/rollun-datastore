<?php

namespace rollun\test\unit\Repository;

use PHPUnit\Framework\TestCase;
use rollun\repository\Interfaces\ModelCastingInterface;
use rollun\repository\ModelAbstract;

class ModelAbstractTest extends TestCase
{
    public function testSetAttributeByMethod()
    {
        $model = new class extends ModelAbstract {};

        $model->setAttribute('field', 'test');

        $this->assertEquals('test', $model->getAttributes()['field']);
    }

    public function testSetAttributeByProperty()
    {
        $model = new class extends ModelAbstract {};

        $model->field = 'test';

        $this->assertEquals('test', $model->getAttributes()['field']);
    }

    public function testGetAttributeByMethod()
    {
        $model = new class extends ModelAbstract {};
        $model->field = 'test';

        $field = $model->getAttribute('field');

        $this->assertEquals('test', $field);
    }

    public function getGetAttributeProperty()
    {
        $model = new class extends ModelAbstract {};
        $model->field = 'test';

        $field = $model->field;

        $this->assertEquals('test', $field);
    }

    public function testFillAttributes()
    {
        $data = [
            'field' => 'test',
            'name' => 'hello',
        ];
        $model = new class ($data) extends ModelAbstract {};

        $this->assertEquals($data['field'], $model->getAttributes()['field']);
        $this->assertEquals($data['name'], $model->getAttributes()['name']);
    }

    public function testHidden()
    {
        $data = [
            'field' => 'test',
            'name' => 'hello',
            'hidden' => true,
        ];
        $model = new class ($data) extends ModelAbstract {
            public function hidden(): array
            {
                return ['hidden'];
            }
        };

        $array = $model->toArray();

        $this->assertFalse(isset($array['hidden']));
    }

    public function testSetMutatedAttribute()
    {
        $data = [
            'field' => 'test',
            'name' => 'hello',
        ];
        $model = new class ($data) extends ModelAbstract {
            public function setFieldAttribute($value)
            {
                return 'mutated-' . $value;
            }
        };

        $expected = 'mutated-' . $data['field'];
        $this->assertEquals($expected, $model->field);
    }

    public function testGetMutatedAttribute()
    {
        $data = [
            'field' => 'test',
            'name' => 'hello',
        ];
        $model = new class ($data) extends ModelAbstract {
            public function getFieldAttribute($value)
            {
                return 'mutated-' . $value;
            }
        };

        $expected = 'mutated-' . $data['field'];
        $this->assertEquals($expected, $model->field);
    }

    public function testIsChanged()
    {
        $data = [
            'field' => 'test',
            'name' => 'hello',
        ];
        $model = new class ($data, true) extends ModelAbstract {
            public function setFieldAttribute($value)
            {
                return 'mutated-' . $value;
            }
        };

        $this->assertFalse($model->isChanged());

        $model->field = 'changed';

        $this->assertTrue($model->isChanged());
    }

    public function testGetChangedAttributes()
    {
        $data = [
            'field' => 'test',
            'name' => 'hello',
        ];
        $model = new class ($data, true) extends ModelAbstract {};

        $this->assertEmpty($model->getChanges());

        $model->field = 'changed';

        $this->assertSame(['field' => 'changed'], $model->getChanges());
    }

    /*public function testGetMutatedAttributes()
    {
        $data = [
            'field' => 'test',
            'name' => 'hello',
        ];
        $model = new class($data) extends ModelAbstract {
            public function getFieldAttribute($value)
            {
                return 'mutated-' . $value;
            }
        };
        $attributes = $model->getAttributes();
        $expected = 'mutated-' . $data['field'];
        $this->assertEquals($expected, $attributes['field']);
    }*/

    public function testChangedNumeric()
    {
        $data = [
            'field' => '1.0',
        ];
        $model = new class ($data, true) extends ModelAbstract {};

        $model->field = '1.00';

        $this->assertEmpty($model->getChanges());
    }

    public function testChangesNew()
    {
        $data = [
            'field' => 'test',
        ];
        $model = new class ($data) extends ModelAbstract {};

        $this->assertEquals($data, $model->getChanges());

        $model->field = 'hello';

        $this->assertEquals(['field' => 'hello'], $model->getChanges());
    }

    /**
     * @todo move to casting test
     */
    public function testCasing()
    {
        $data = [
            'field1' => '1234',
            'field2' => '12.34',
            'field3' => ['key' => 'value'],
            'field4' => ['key' => 'value'],
            'field5' => ['key' => 'value'],
            'field6' => ['value1', 'value2'],
            'field7' => (object) ['key' => 'value'],
            'field8' => [],
            'field9' => 'string',
            'field10' => 'string',
            'field11' => null,
            'field12' => '',
            'field13' => [],
            'field14' => '{"key": "value"}',
            'field15' => '{"key": "value"}',
        ];

        $custom = new class implements ModelCastingInterface {
            public function get($value)
            {
                return explode('/', $value);
            }

            public function set($value)
            {
                return str_replace(['1', '2'], ['-one', '-two'], implode('/', $value));
            }
        };

        $casting = [
            'field1' => ModelCastingInterface::CAST_INT,
            'field2' => ModelCastingInterface::CAST_FLOAT,
            'field3' => ModelCastingInterface::CAST_JSON,
            'field4' => ModelCastingInterface::CAST_SERIALIZE,
            'field5' => ModelCastingInterface::CAST_OBJECT,
            'field6' => $custom::class,
            'field7' => ModelCastingInterface::CAST_ARRAY,
            'field8' => ModelCastingInterface::CAST_ARRAY,
            'field9' => ModelCastingInterface::CAST_ARRAY,
            'field10' => ModelCastingInterface::CAST_OBJECT,
            'field11' => ModelCastingInterface::CAST_ARRAY,
            'field12' => ModelCastingInterface::CAST_OBJECT,
            'field13' => ModelCastingInterface::CAST_OBJECT,
            'field14' => ModelCastingInterface::CAST_ARRAY,
            'field15' => ModelCastingInterface::CAST_OBJECT,
        ];

        $model = new class (array_merge(['casting' => $casting], $data)) extends ModelAbstract {};

        $this->assertIsInt($model->field1);
        $this->assertIsFloat($model->field2);
        $this->assertIsObject($model->field3);
        $this->assertIsArray($model->field4);
        $this->assertIsObject($model->field5);
        $this->assertEquals(['value-one', 'value-two'], $model->field6);
        $this->assertIsArray($model->field7);
        $this->assertEquals([], $model->field8);
        $this->assertEquals(['string'], $model->field9);
        $this->assertIsObject($model->field10);
        $this->assertEquals([], $model->field11);
        $this->assertEquals(null, $model->getRawAttribute('field11'));
        $this->assertEquals(null, $model->field12);
        $this->assertEquals('', $model->getRawAttribute('field12'));
        $this->assertIsObject($model->field13);
        $this->assertEquals(['key' => 'value'], $model->field14);
        $this->assertEquals((object) ['key' => 'value'], $model->field15);
    }
}
