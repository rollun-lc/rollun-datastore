<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 28.10.16
 * Time: 1:29 PM
 */

return [
    'dataStore' => [
        'product' => [
            'class' => rollun\datastore\DataStore\Composite\Composite::class,
            'tableName' => 'product'
        ],
        'images' => [
            'class' => rollun\datastore\DataStore\Composite\Composite::class,
            'tableName' => 'images'
        ],
        'category' => [
            'class' => rollun\datastore\DataStore\Composite\Composite::class,
            'tableName' => 'category'
        ],
        'category_products' => [
            'class' => rollun\datastore\DataStore\Composite\Composite::class,
            'tableName' => 'category_products'
        ],
    ]
];