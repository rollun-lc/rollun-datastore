<?php

namespace rollun\repository;


use Psr\Log\LoggerInterface;
use rollun\datastore\DataStore\DataStoreAbstract;
use rollun\dic\InsideConstruct;
use rollun\repository\Interfaces\FieldMapperInterface;
use rollun\repository\Interfaces\ModelRepositoryInterface;
use rollun\repository\Interfaces\ModelInterface;
use Xiag\Rql\Parser\Node\Query\ArrayOperator\InNode;
use Xiag\Rql\Parser\Query;

/**
 * Class ModelRepository
 *
 * @package rollun\datastore\DataStore\Model
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
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * ModelRepository constructor.
     *
     * @param DataStoreAbstract $dataStore
     * @param ModelInterface $modelClass,
     * @param FieldMapperInterface $mapper
     */
    public function __construct(
        DataStoreAbstract $dataStore,
        string $modelClass,
        FieldMapperInterface $mapper = null,
        LoggerInterface $logger
    ) {
        $this->dataStore = $dataStore;
        $this->modelClass = $modelClass;
        $this->mapper = $mapper;
        $this->logger = $logger;
    }

    /**
     * @return string[]
     */
    public function __sleep()
    {
        return [
            'dataStore',
            'modelClass',
            'mapper',
        ];
    }

    public function __wakeup()
    {
        InsideConstruct::initWakeup([
            'logger' => LoggerInterface::class,
        ]);
    }

    /**
     * @return DataStoreAbstract
     */
    public function getDataStore()
    {
        return $this->dataStore;
    }

    /**
     * @param $id
     *
     * @return bool
     */
    public function has($id): bool
    {
        return $this->dataStore->has($id);
    }

    /**
     * @param array $record
     *
     * @return ModelInterface
     */
    protected function make($record = []): ModelInterface
    {
        if ($this->mapper) {
            $record = $this->mapper->map($record);
        }

        /** @var ModelInterface $model */
        $model = new $this->modelClass($record);

        $model->setExists(true);

        return $model;
    }

    /**
     * @param $records
     *
     * @return array
     */
    protected function makeModels($records)
    {
        $models = [];
        foreach ($records as $data) {
            $models[] = $this->make($data);
        }
        return $models;
    }

    /**
     * @todo Test
     *
     * @param ModelInterface $model
     *
     * @return bool
     */
    public function save(ModelInterface $model): bool
    {
        if ($model->isExists()) {
            /*$identifier = $this->dataStore->getIdentifier();
            if (!isset($model->{$identifier}) || !$this->dataStore->has($model->{$identifier})) {
                throw new \Exception();
            }*/
            return $this->updateModel($model);
        }

        $result = $this->insertModel($model);

        if ($result) {
            $model->setExists(true);
            return $result;
        }

        throw new \Exception('Can not save model');
    }

    /**
     * @param $model
     *
     * @return bool
     * 
     * @todo * @todo update field created_at
     */
    public function insertModel(ModelInterface $model)
    {
        $record = $this->dataStore->create($model->toArray());

        // TODO
        if ($record) {
            $identifier = $this->dataStore->getIdentifier();
            if (isset($record[$identifier])) {
                $model->{$identifier} = $record[$identifier];
                $model->setExists(true);
            }
        }

        return (bool) $record;
    }

    /**
     * @todo
     *
     * @param $models
     */
    public function multiInserModels($models)
    {
        $data = [];
        foreach ($models as $model) {
            $data[] = $model->toArray();
        }
        $multiInsertedIds = $this->dataStore->multiCreate($data);

        $identifier = $this->dataStore->getIdentifier();
        foreach ($models as $key => $model) {
            $model->{$identifier} = $multiInsertedIds[$key];
            $model->setExists(true);
        }

        return $multiInsertedIds;
    }

    /**
     * @param $models
     *
     * @return array
     *
     * @throws \rollun\datastore\DataStore\DataStoreException
     */
    public function multiUpdateModels($models)
    {
        $identifier = $this->dataStore->getIdentifier();
        $ids = array_column($models, $identifier);
        if (!$this->isSameChanges($models)) {
            foreach ($models as $model) {
                $this->updateModel($model);
            }
        } else {
            $changes = $models[0]->getChanges();
            $query = new Query();
            $query->setQuery(new InNode($identifier, $ids));
            $this->dataStore->queriedUpdate($changes, $query);
        }

        return $ids;
    }

    protected function isSameChanges($models)
    {
        $changes = [];
        foreach ($models as $model) {
            foreach ($model->getChanges() as $field => $value) {
                if (!isset($changes[$field])) {
                    $changes[$field] = $value;
                    continue;
                }

                if ($changes[$field] != $value) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param ModelInterface[] $models
     *
     * @return array
     *
     * @todo
     */
    public function multiSave(array $models)
    {
        $multiUpdatedIds = $multiInsertedIds = $notChangedIds = [];

        $identifier = $this->dataStore->getIdentifier();

        foreach ($models as $model) {
            if (!$model->isChanged()) {
                $notChangedIds[] = $model->{$identifier};
                continue;
            }

            if ($model->isExists() /*&& $model->isChanged()*/) {
                $multiUpdated[] = $model;
            } else {
                $multiCreated[] = $model;
            }
        }

        if (!empty($multiUpdated)) {
            $multiUpdatedIds = $this->multiUpdateModels($multiUpdated);
        }

        if (!empty($multiCreated)) {
            $multiInsertedIds = $this->multiInserModels($multiCreated);
        }

        return array_merge($notChangedIds, $multiUpdatedIds, $multiInsertedIds);
    }

    /**
     * @param $model
     *
     * @return bool
     * 
     * @todo update field updated_at
     * @todo updating abstract model original
     */
    public function updateModel(ModelInterface $model)
    {
        $result = $this->dataStore->update($model->toArray());
        if ($result) {
            $model->setExists(true);
        }

        return (bool) $result;
    }

    /**
     * @param $id
     *
     * @return ModelInterface|null
     */
    public function findById($id): ?ModelInterface
    {
        $record = $this->dataStore->read($id);
        if ($record) {
            return $this->make($record);
        }

        return $record;
    }

    /**
     * @param Query $query
     *
     * @return array
     */
    public function find(Query $query): array
    {
        $records =  $this->dataStore->query($query);
        if ($records) {
            return $this->makeModels($records);
        }

        return $records;
    }

    /**
     * @return array
     */
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

    /**
     * @param ModelInterface $model
     *
     * @return bool
     */
    public function remove(ModelInterface $model): bool
    {$identifier
         = $this->dataStore->getIdentifier();
        if (isset($model->{$identifier}) && $this->dataStore->has($model->{$identifier})) {
            return (bool) $this->dataStore->delete($model->{$identifier});
        }

        return false;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return (int) $this->dataStore->count();
    }
}