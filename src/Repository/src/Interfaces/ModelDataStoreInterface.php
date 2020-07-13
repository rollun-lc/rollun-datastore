<?php


namespace rollun\repository\Interfaces;


use rollun\repository\ModelAbstract;
use Xiag\Rql\Parser\Query;

interface ModelDataStoreInterface
{
    public function make($attributes = []): ModelInterface;

    public function save(ModelInterface $model);

    public function find(Query $query);

    public function findById($id);

    /**
     * @todo
     *
     * @param $id
     *
     * @return mixed
     */
    public function deleteById($id);
}