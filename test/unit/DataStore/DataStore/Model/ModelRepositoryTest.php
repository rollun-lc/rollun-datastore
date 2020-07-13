<?php

namespace rollun\test\unit\DataStore\DataStore\Model;


use PHPUnit\Framework\TestCase;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\Memory;
use rollun\datastore\DataStore\Model\ModelDataStore;
use rollun\datastore\DataStore\Model\Model;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\EqNode;
use Xiag\Rql\Parser\Query;

class ModelRepositoryTest extends TestCase
{
    protected $instance;

    protected function setUp()
    {

    }

    protected function getItem()
    {
        return [
            'id' => 1,
            'field' => 'test',
        ];
    }

    public function testCreate()
    {
        $dataStore = $this->createMock(Memory::class);
        $dataStore->method('create')->willReturn($this->getItem());
        $model = new class extends Model {};
        $instance = new ModelDataStore($dataStore, $model);

        $result = $instance->create($this->getItem());

        $this->assertSame($this->getItem(), $result);
    }

    public function testHas()
    {
        $dataStore = $this->createMock(Memory::class);
        $dataStore->method('has')->willReturn(true);
        $model = new class extends Model {};
        $instance = new ModelDataStore($dataStore, $model);

        $this->assertTrue($instance->has(1));
    }

    public function testRead()
    {
        $dataStore = $this->createMock(Memory::class);
        $dataStore->method('read')->willReturn($this->getItem());
        $model = new class extends Model {};
        $instance = new ModelDataStore($dataStore, $model);

        $result = $instance->asArray()->read(1);

        $this->assertEquals(1, $result['id']);
        $this->assertEquals('test', $result['field']);
    }

    public function testUpdate()
    {
        $dataStore = $this->createMock(Memory::class);
        $newData = [
            'id' => 1,
            'field' => 'hello',
        ];
        $dataStore->method('update')->willReturn($newData);
        $model = new class extends Model {};
        $instance = new ModelDataStore($dataStore, $model);

        $result = $instance->update($newData);
        $this->assertEquals($newData['field'], $result['field']);
    }

    public function testQuery()
    {
        $dataStore = $this->createMock(Memory::class);
        $dataStore->method('query')->willReturn([$this->getItem()]);
        $model = new class extends Model {};
        $instance = new ModelDataStore($dataStore, $model);

        $query = new Query();
        $query->setQuery(new EqNode('field', 'test'));
        $results = $instance->asArray()->query($query);

        $this->assertSame([$this->getItem()], $results);
    }

    public function testDelete()
    {
        $dataStore = $this->createMock(Memory::class);
        $dataStore->method('delete')->willReturn($this->getItem());
        $model = new class extends Model {};
        $instance = new ModelDataStore($dataStore, $model);

        $result = $instance->delete(1);

        $this->assertSame($this->getItem(), $result);
    }

    /*public function testDeleteAll()
    {
        $dataStore = $this->createMock(Memory::class);
        $model = new class extends Model {};
        $instance = new ModelRepository($dataStore, $model);

        $this->expectException(DataStoreException::class);

        $instance->deleteAll();
    }*/
}