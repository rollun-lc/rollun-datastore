<?php

namespace rollun\test\unit\Repository;


use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use rollun\datastore\DataStore\Memory;
use rollun\repository\Interfaces\FieldMapperInterface;
use rollun\repository\Interfaces\ModelInterface;
use rollun\repository\ModelRepository;
use rollun\repository\ModelAbstract;
use Graviton\RqlParser\Parser\Node\Query\ScalarOperator\EqNode;
use Graviton\RqlParser\Parser\Query;

class ModelRepositoryTest extends TestCase
{
    protected function getItem()
    {
        return [
            'id' => 1,
            'field' => 'test',
        ];
    }

    protected function createModelInterface($data = [])
    {
        return new class($data) implements ModelInterface {
            public $id;
            public $field;
            public $hello;

            protected $origin = [];

            protected $exists = false;

            public function __construct($attributes)
            {
                foreach ($attributes as $key => $value) {
                    $this->{$key} = $value;
                    $this->origin[$key] = $value;
                }
            }

            public function toArray(): array
            {
                return ['id' => $this->id, 'field' => $this->field];
            }

            public function isChanged(): bool {
                return !$this->exists || !empty($this->getChanges());
            }

            public function getChanges(): array {
                $changes = [];
                foreach (['id', 'field', 'hello'] as $field) {
                    if (!isset($this->origin[$field]) && !isset($this->{$field})) {
                        continue;
                    }
                    if ($this->{$field} != $this->origin[$field]) {
                        $changes[$field] = $this->{$field};
                    }
                }

                return $changes;
            }

            public function isExists(): bool {
                return $this->exists;
            }

            public function setExists(bool $exists): void {
                $this->exists = $exists;
            }
        };
    }

    protected function createRepository($dataStore): ModelRepository
    {
        global $container;
        $logger = $container->get(LoggerInterface::class);
        $model = $this->createModelInterface();

        return new ModelRepository($dataStore, get_class($model), null, $logger);
    }

    public function testCreate()
    {
        $dataStore = new Memory();
        $repository = $this->createRepository($dataStore);

        $item = $this->createModelInterface($this->getItem());;
        $repository->save($item);

        $this->assertSame($this->getItem(), $dataStore->read(1));
    }

    public function testUpdate()
    {
        $dataStore = new Memory();
        $repository = $this->createRepository($dataStore);

        $item = $this->createModelInterface($this->getItem());
        //$old->setExists(true);
        $repository->save($item);

        $item->field = 'hello';

        $repository->save($item);
        $this->assertSame(['id' => 1, 'field' => 'hello'], $dataStore->read(1));
    }

    public function testRead()
    {
        $dataStore = new Memory();
        $dataStore->create($this->getItem());
        $repository = $this->createRepository($dataStore);

        $result = $repository->findById(1);

        $this->assertIsObject($result);
    }

    public function testQuery()
    {
        $dataStore = new Memory();
        $dataStore->create($this->getItem());
        $repository = $this->createRepository($dataStore);

        $query = new Query();
        $query->setQuery(new EqNode('field', 'test'));
        $results = $repository->find($query);

        $this->assertEquals(1, count($results));
    }

    public function testDelete()
    {
        $dataStore = new Memory();
        $dataStore->create($this->getItem());
        $repository = $this->createRepository($dataStore);

        $repository->removeById(1);

        $this->assertEmpty($dataStore->read(1));
    }

    public function testReadModelAbstract()
    {
        $dataStore = new Memory();
        $dataStore->create($this->getItem());
        $repository = $this->createRepository($dataStore);

        $result = $repository->findById(1);

        $this->assertIsObject($result);
        $this->assertInstanceOf(ModelInterface::class, $result);
    }

    public function testRepositoryWithMapper()
    {
        $dataStore = new Memory();
        $dataStore->create($this->getItem());
        $model = new class() extends ModelAbstract {};
        $mapper = new class () implements FieldMapperInterface{
            public function map(array $data): array
            {
                return ['id' => $data['id'], 'hello' => $data['field']];
            }
        };
        global $container;
        $logger = $container->get(LoggerInterface::class);
        $repository = new ModelRepository($dataStore, get_class($model), $mapper, $logger);

        $result = $repository->findById(1);

        $this->assertEquals($result->hello, $this->getItem()['field']);
    }

    public function testMultiCreate()
    {
        $dataStore = new Memory();
        $dataStore->create($this->getItem());
        $repository = $this->createRepository($dataStore);
        $models = [
            $repository->findById(1),
            $this->createModelInterface(['id' => 2, 'field' => 'field2']),
            $this->createModelInterface(['id' => 3, 'field' => 'field3']),
        ];
        $results = $repository->multiSave($models);

        $this->assertEquals(3, count($results));
        $this->assertEquals('test', $dataStore->read(1)['field']);
        $this->assertEquals('field2', $dataStore->read(2)['field']);
        $this->assertEquals('field3', $dataStore->read(3)['field']);
    }

    public function testMultiUpdateSame()
    {
        $dataStore = new Memory();
        $dataStore->create(['id' => 1, 'field' => 'field1']);
        $dataStore->create(['id' => 2, 'field' => 'field2']);

        $repository = $this->createRepository($dataStore);

        $models = $repository->find(new Query());

        foreach ($models as $model) {
            $model->field = 'changed';
        }

        $model = $this->createModelInterface(['id' => 3, 'field' => 'field3']);
        $models[] = $model;

        $results = $repository->multiSave($models);

        $this->assertEquals(3, count($results));
        $this->assertEquals('changed', $dataStore->read(1)['field']);
        $this->assertEquals('changed', $dataStore->read(2)['field']);
        $this->assertEquals('field3', $dataStore->read(3)['field']);
    }

    public function testMultiUpdateNotSame()
    {
        $dataStore = new Memory();
        $dataStore->create(['id' => 1, 'field' => 'field1']);
        $dataStore->create(['id' => 2, 'field' => 'field2']);

        $repository = $this->createRepository($dataStore);

        $models = $repository->find(new Query());

        $models[0]->field = 'changed1';
        $models[1]->field = 'changed2';

        $model = $this->createModelInterface(['id' => 3, 'field' => 'field3']);
        $models[] = $model;

        $results = $repository->multiSave($models);

        $this->assertEquals(3, count($results));
        $this->assertEquals('changed1', $dataStore->read(1)['field']);
        $this->assertEquals('changed2', $dataStore->read(2)['field']);
        $this->assertEquals('field3', $dataStore->read(3)['field']);
    }
}