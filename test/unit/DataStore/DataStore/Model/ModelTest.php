<?php


namespace rollun\test\unit\DataStore\DataStore\Model;


use PHPUnit\Framework\TestCase;
use rollun\datastore\DataStore\Model\Model;

class ModelTest extends TestCase
{
    public function testSetAttributeByMethod()
    {
        $model = new class extends Model{};

        $model->setAttribute('field', 'test');

        $this->assertEquals('test', $model->getAttributes()['field']);
    }

    public function testSetAttributeByProperty()
    {
        $model = new class extends Model{};

        $model->field = 'test';

        $this->assertEquals('test', $model->getAttributes()['field']);
    }

    public function testGetAttributeByMethod()
    {
        $model = new class extends Model{};
        $model->field = 'test';

        $field = $model->getAttribute('field');

        $this->assertEquals('test', $field);
    }

    public function getGetAttributeProperty()
    {
        $model = new class extends Model{};
        $model->field = 'test';

        $field = $model->field;

        $this->assertEquals('test', $field);
    }

    public function testFillAttributes()
    {
        $model = new class extends Model{};

        $data = [
            'field' => 'test',
            'name' => 'hello',
        ];
        $model->fill($data);

        $this->assertEquals($data['field'], $model->getAttributes()['field']);
        $this->assertEquals($data['name'], $model->getAttributes()['name']);
    }
}