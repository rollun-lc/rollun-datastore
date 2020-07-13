<?php

use rollun\datastore\DataStore\Factory\DataStoreAbstractFactory;
use rollun\datastore\DataStore\Memory;
use rollun\repository\Factory\ModelRepositoryAbstractFactory;
use rollun\repository\Model\SimpleModelExtendedAbstract;
use rollun\repository\ModelRepository;

return[
    ModelRepositoryAbstractFactory::KEY_MODEL_REPOSITORY => [
        'testModelRepository' => [
            ModelRepositoryAbstractFactory::KEY_CLASS => ModelRepository::class,
            ModelRepositoryAbstractFactory::KEY_DATASTORE => Memory::class,
            ModelRepositoryAbstractFactory::KEY_MODEL => SimpleModelExtendedAbstract::class,
        ]
    ],
    DataStoreAbstractFactory::KEY_DATASTORE => [
        Memory::class => [
            DataStoreAbstractFactory::KEY_CLASS => Memory::class
        ]
    ]
];