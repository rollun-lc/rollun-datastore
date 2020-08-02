<?php

namespace rollun\repository;


use rollun\datastore\DataStore\DataStoreAbstract;
use rollun\repository\Interfaces\FieldMapperInterface;
use rollun\repository\Interfaces\ModelHiddenFieldInterface;
use rollun\repository\Interfaces\ModelRepositoryInterface;
use rollun\repository\Interfaces\ModelInterface;
use Xiag\Rql\Parser\Query;
use Zend\Hydrator\HydratorInterface;
use Zend\Hydrator\ObjectPropertyHydrator;

/**
 * Class ModelRepository
 *
 * @package rollun\datastore\DataStore\Model
 *
 * @todo getDataStore
 */
class ModelRepository implements ModelRepositoryInterface
{
    /**
     * @var \rollun\datastore\DataStore\DataStoreAbstract
     */
    protected $dataStore;

    /**
     * @var ModelInterface
     */
    protected $modelClass;

    /**
     * @var FieldMapperInterface
     */
    protected $mapper;

    /**
     * ModelRepository constructor.
     *
     * @param DataStoreAbstract $dataStore
     * @param ModelInterface $modelClass
     */
    public function __construct(
        DataStoreAbstract $dataStore,
        string $modelClass,
        FieldMapperInterface $mapper = null
    ) {
        $this->dataStore = $dataStore;
        $this->modelClass = $modelClass;
        $this->mapper = $mapper;
    }

    public function __sleep()
    {
        return [
            'dataStore',
            'modelClass',
            'mapper',
        ];
    }

    /**
     * @return DataStoreAbstract
     */
    public function getDataStore()
    {
        return $this->dataStore;
    }


    public function has($id)
    {
        return $this->dataStore->has($id);
    }

    protected function make($record = []): ModelInterface
    {
        /*$model = clone $this->model;

        $this->resolver->fill($data, $model);*/

        if ($this->mapper) {
            $record = $this->mapper->map($record);
        }

        $model = new $this->modelClass($record);

        return $model;
    }

    protected function makeModels($records)
    {
        $models = [];
        foreach ($records as $data) {
            $models[] = $this->make($data);
        }
        return $models;
    }

    public function save(ModelInterface $model): bool
    {
        $identifier = $this->dataStore->getIdentifier();
        if (isset($model->{$identifier}) && $this->dataStore->has($model->{$identifier})) {
            return $this->updateModel($model);
        }

        return $this->insertModel($model);
    }

    /**
     * @param $model
     * @return bool
     * 
     * @todo * @todo update field created_at
     */
    public function insertModel($model)
    {
        $record = $this->dataStore->create($model->toArray());

        // TODO
        if ($record) {
            $identifier = $this->dataStore->getIdentifier();
            if (isset($record[$identifier])) {
                $model->{$identifier} = $record[$identifier];
            }
        }

        return (bool) $record;
    }

    /**
     * @param $model
     * @return bool
     * 
     * @todo update field updated_at
     */
    public function updateModel($model)
    {
        return (bool) $this->dataStore->update($model->toArray());
    }

    public function findById($id): ?ModelInterface
    {
        $record = $this->dataStore->read($id);
        if ($record) {
            return $this->make($record);
        }

        return $record;
    }

    public function find(Query $query): array
    {
        $records =  $this->dataStore->query($query);
        if ($records) {
            return $this->makeModels($records);
        }

        return $records;
    }

    public function all(): array
    {
        $query = new Query();
        return $this->find($query);
    }

    /**
     * @todo
     *
     * @param $id
     *
     * @return mixed|void
     */
    public function removeById($id): bool
    {
        return (bool) $this->dataStore->delete($id);
    }

    public function remove(ModelInterface $model): bool
    {
        $identifier = $this->dataStore->getIdentifier();
        if (isset($model->{$identifier}) && $this->dataStore->has($model->{$identifier})) {
            return (bool) $this->dataStore->delete($model->{$identifier});
        }

        return false;
    }

    public function count(): int
    {
        return (int) $this->dataStore->count();
    }
}