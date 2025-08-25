<?php

namespace rollun\repository\Interfaces;

use Graviton\RqlParser\Query;
use rollun\datastore\DataStore\Interfaces\DataStoreInterface;

/**
 * Interface ModelRepositoryInterface
 *
 * @package rollun\repository\Interfaces
 */
interface ModelRepositoryInterface
{
    /**
     * @param ModelInterface $model
     *
     * @return bool
     */
    public function save(ModelInterface $model): bool;

    /**
     * @param ModelInterface[] $models
     *
     * @return mixed
     *
     * @todo
     */
    public function multiSave(array $models);

    /**
     * @param Query $query
     *
     * @return array
     */
    public function find(Query $query): array;

    /**
     * @param $id
     *
     * @return ModelInterface|null
     */
    public function findById($id): ?ModelInterface;

    /**
     * @param ModelInterface $model
     *
     * @return bool
     */
    public function remove(ModelInterface $model): bool;

    /**
     * @todo ?
     *
     * @param $id
     *
     * @return mixed
     */
    public function removeById($id): bool;

    /**
     * @return int
     */
    public function count(): int;

    /**
     * @return DataStoreInterface
     */
    public function getDataStore();

    /**
     * @param $id
     *
     * @return bool
     */
    public function has($id): bool;
}
