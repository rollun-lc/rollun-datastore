<?php


namespace rollun\datastore\DataStore\Interfaces;


use rollun\datastore\DataStore\Model\Model;

interface ModelDataStoreInterface
{
    public function asArray();

    public function makeModel($attributes = []): Model;
}