<?php

namespace rollun\repository;


use rollun\datastore\DataStore\DataStoreAbstract;
use rollun\repository\Interfaces\ModelDataStoreInterface;
use rollun\repository\Interfaces\ModelInterface;
use Xiag\Rql\Parser\Query;
use Zend\Hydrator\ObjectPropertyHydrator;

/**
 * Class ModelDataStore
 * @package rollun\datastore\DataStore\Model
 *
 * @todo getDataStore
 */
class ModelRepository implements ModelDataStoreInterface
{
    /**
     * @var \rollun\datastore\DataStore\DataStoreAbstract
     */
    protected $dataStore;

    /**
     * @var ModelInterface
     */
    protected $model;

    /**
     * ModelRepository constructor.
     *
     * @param DataStoreAbstract $dataStore
     * @param ModelInterface $model
     */
    public function __construct(DataStoreAbstract $dataStore, ModelInterface $model)
    {
        $this->dataStore = $dataStore;
        $this->model = $model;
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

    public function make($data = []): ModelInterface
    {
        $model = clone $this->model;

        if ($model instanceof ModelAbstract) {
            $model->fill($data);
        } else {
            $hydrator = new ObjectPropertyHydrator();
            $hydrator->hydrate($data, $model);
        }

        return $model;
    }


    public function save(ModelInterface $model)
    {
        $identifier = $this->dataStore->getIdentifier();
        if (isset($model->{$identifier}) && $this->dataStore->has($model->{$identifier})) {
            $this->dataStore->update($model->toArray());
        } else {
            $this->dataStore->create($model->toArray());
        }
    }

    public function findById($id)
    {
        $result = $this->dataStore->read($id);
        if ($result) {
            return $this->make($result);
        }

        return $result;
    }

    public function find(Query $query)
    {
        $results =  $this->dataStore->query($query);
        if ($results) {
            $models = [];
            foreach ($results as $data) {
                $models[] = $this->make($data);
            }
            return $models;
        }

        return $results;
    }

    /**
     * @todo
     *
     * @param $id
     *
     * @return mixed|void
     */
    public function deleteById($id)
    {
        $this->dataStore->delete($id);
    }
}