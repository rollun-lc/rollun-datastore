<?php


namespace rollun\repository\Interfaces;


use rollun\repository\ModelAbstract;
use Xiag\Rql\Parser\Query;

interface ModelRepositoryInterface
{
    public function save(ModelInterface $model): bool;

    public function find(Query $query): array;

    public function findById($id): ModelInterface;

    public function remove(ModelInterface $model): bool;

    /**
     * @todo ?
     *
     * @param $id
     *
     * @return mixed
     */
    public function removeById($id): bool;

    public function getDataStore();
}