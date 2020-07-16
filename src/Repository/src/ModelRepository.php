<?php

namespace rollun\repository;


use rollun\datastore\DataStore\DataStoreAbstract;
use rollun\repository\Interfaces\FieldResolverInterface;
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
    protected $model;

    /**
     * @var FieldResolverInterface
     */
    protected $resolver;

    /**
     * ModelRepository constructor.
     *
     * @param DataStoreAbstract $dataStore
     * @param ModelInterface $model
     */
    public function __construct(
        DataStoreAbstract $dataStore,
        ModelInterface $model,
        FieldResolverInterface $resolver = null
    ) {
        $this->dataStore = $dataStore;
        $this->model = $model;
        $this->resolver = $resolver;
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

    protected function make($data = []): ModelInterface
    {
        /*$model = clone $this->model;

        $this->resolver->fill($data, $model);*/

        if ($this->resolver) {
            $data = $this->resolver->resolve($data);
        }

        $model = new $this->model($data);

        return $model;
    }


    public function save(ModelInterface $model): bool
    {
        $identifier = $this->dataStore->getIdentifier();
        if (isset($model->{$identifier}) && $this->dataStore->has($model->{$identifier})) {
            return (bool) $this->dataStore->update($model->toArray());
        } else {
            return (bool) $this->dataStore->create($model->toArray());
        }
    }

    public function findById($id): ModelInterface
    {
        $result = $this->dataStore->read($id);
        if ($result) {
            return $this->make($result);
        }

        return $result;
    }

    public function find(Query $query): array
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
}