<?php


namespace rollun\test\unit\Repository;


use PHPUnit\Framework\TestCase;
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
        $model = new class($data) extends ModelAbstract {};

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
        $model = new class($data) extends ModelAbstract {
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
        $model = new class($data) extends ModelAbstract {
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
        $model = new class($data) extends ModelAbstract {
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
        $model = new class($data) extends ModelAbstract {
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
        $model = new class($data) extends ModelAbstract {};

        $this->assertEmpty($model->getChangedAttributes());

        $model->field = 'changed';

        $this->assertSame(['field' => 'changed'], $model->getChangedAttributes());
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
}