<?php


namespace rollun\repository;


use rollun\repository\Factory\ModelRepositoryAbstractFactory;

class ConfigProvider
{
    public function __invoke()
    {
        return [
            'dependencies' => [
                'abstract_factories' => [
                    ModelRepositoryAbstractFactory::class,
                ]
            ],
        ];
    }
}