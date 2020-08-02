<?php


namespace rollun\test\unit\Repository;


use PHPUnit\Framework\TestCase;
use rollun\datastore\DataStore\DataStoreAbstract;
use rollun\datastore\DataStore\Memory;
use rollun\repository\Interfaces\ModelInterface;
use rollun\repository\Interfaces\ModelRepositoryInterface;
use Xiag\Rql\Parser\Query;

class ModelRepositoryInterfaceTest extends TestCase
{
    protected function getItem()
    {
        return [
            'id' => 1,
            'field' => 'test',
        ];
    }

    public function createModelRepositoryInterface($dataStore)
    {
        return new class($dataStore) implements ModelRepositoryInterface {
            protected $dataStore;

            public function __construct(DataStoreAbstract $dataStore)
            {
                $this->dataStore = $dataStore;
            }

            public function make($attributes = []): ModelInterface {}

            public function save(ModelInterface $model): bool {}

            public function find(Query $query): array {}

            public function removeById($id): bool {}

            public function remove(ModelInterface $model): bool {}

            public function count(): int {}

            public function getDataStore() {}

            public function findById($id): ModelInterface
            {
                $result = $this->dataStore->read($id);
                return new class($result['id'], $result['field']) implements ModelInterface {
                    public $id;

                    public $field;

                    public function __construct($id, $field)
                    {
                        $this->id = $id;
                        $this->field = $field;
                    }

                    public function toArray(): array
                    {
                        return ['id' => $this->id, 'field' => $this->field];
                    }

                    public function isChanged(): bool {}

                    public function getChanged(): array{}

                    public function isExists(): bool{}

                    public function setExists(bool $exists): void{}
                };
            }
        };
    }

    public function testModelRepository()
    {
        $dataStore = new Memory();
        $dataStore->create($this->getItem());
        $repository = $this->createModelRepositoryInterface($dataStore);
        $model = $repository->findById($this->getItem()['id']);

        $this->assertSame($this->getItem(), $model->toArray());
    }
}