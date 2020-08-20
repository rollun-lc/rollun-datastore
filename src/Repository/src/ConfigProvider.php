<?php


namespace rollun\repository;


use rollun\repository\Factory\ModelRepositoryAbstractFactory;

/**
 * Class ConfigProvider
 *
 * @package rollun\repository
 */
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