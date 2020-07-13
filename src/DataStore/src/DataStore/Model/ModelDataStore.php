<?php

namespace rollun\datastore\DataStore\Model;


use rollun\datastore\DataStore\DataStoreAbstract;
use rollun\datastore\DataStore\Interfaces\DataStoreInterface;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use rollun\datastore\DataStore\Traits\NoSupportDeleteAllTrait;
use rollun\datastore\DataStore\Interfaces\ModelDataStoreInterface;
use Xiag\Rql\Parser\Query;

class ModelDataStore implements DataStoresInterface, DataStoreInterface, ModelDataStoreInterface
{
    //use NoSupportDeleteAllTrait;

    protected const TYPE_MODEL = 1;

    protected const TYPE_ARRAY = 2;

    protected $type = self::TYPE_MODEL;

    /**
     * @var \rollun\datastore\DataStore\DataStoreAbstract
     */
    protected $dataStore;

    /**
     * @var Model|string|null
     */
    protected $model;

    public function __construct(DataStoreAbstract $dataStore, $model)
    {
        $this->dataStore = $dataStore;
        if ($model) {
            $this->setModel($model);
        }
    }

    public function setModel($model)
    {
        if (is_string($model) && class_exists($model) && is_a($model, Model::class, true)) {
            $model = new $model();
        }
        $model->setDataStore($this);
        $this->model = $model;
    }

    public function has($id)
    {
        return $this->dataStore->has($id);
    }

    public function read($id)
    {
        $result = $this->dataStore->read($id);
        if ($result && $this->type === self::TYPE_MODEL) {
            return $this->makeModel($result);
        }

        return $result;
    }

    public function query(Query $query)
    {
       $results =  $this->dataStore->query($query);
       if ($results && $this->type === self::TYPE_MODEL) {
           $models = [];
           foreach ($results as $data) {
               $models[] = $this->makeModel($data);
           }
           return $models;
       }

       return $results;
    }

    public function asArray()
    {
        $this->type = self::TYPE_ARRAY;

        return $this;
    }

    public function asModel()
    {
        $this->type = self::TYPE_MODEL;
    }

    public function makeModel($data = []): Model
    {
        $model = clone $this->model;

        $model->fill($data);

        return $model;
    }




    public function create($itemData, $rewriteIfExist = false)
    {
        return $this->dataStore->create($itemData, $rewriteIfExist);
    }

    public function update($itemData, $createIfAbsent = false)
    {
        return $this->dataStore->update($itemData, $createIfAbsent);
    }

    public function delete($id)
    {
        return $this->dataStore->delete($id);
    }

    public function multiCreate($records)
    {
        $this->dataStore->multiCreate($records);
    }

    public function multiUpdate($records)
    {
        $this->dataStore->multiUpdate($records);
    }

    public function queriedUpdate($record, Query $query)
    {
        $this->dataStore->queriedUpdate($record, $query);
    }

    public function rewrite($record)
    {
        $this->dataStore->rewrite($record);
    }

    public function queriedDelete(Query $query)
    {
        $this->dataStore->queriedDelete($query);
    }

    public function getIdentifier()
    {
        return $this->dataStore->getIdentifier();
    }

    public function getIterator()
    {
        $this->dataStore->getIterator();
    }

    public function count()
    {
        $this->dataStore->count();
    }

    public function deleteAll()
    {
        $this->dataStore->deleteAll();
    }
}