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
            'class' => rolluncom\datastore\DataStore\Composite\Composite::class,
            'tableName' => 'product'
        ],
        'images' => [
            'class' => rolluncom\datastore\DataStore\Composite\Composite::class,
            'tableName' => 'images'
        ],
        'category' => [
            'class' => rolluncom\datastore\DataStore\Composite\Composite::class,
            'tableName' => 'category'
        ],
        'category_products' => [
            'class' => rolluncom\datastore\DataStore\Composite\Composite::class,
            'tableName' => 'category_products'
        ],
    ]
];