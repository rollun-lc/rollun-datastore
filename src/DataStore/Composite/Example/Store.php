<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 28.10.16
 * Time: 1:04 PM
 */

namespace rollun\datastore\DataStore\Composite\Example;

use rollun\datastore\TableGateway\TableManagerMysql as TableManager;

class Store
{
    //'product'
    const PRODUCT_TABLE_NAME = 'product';
    //'image'
    const IMAGE_TABLE_NAME = 'images';
    //'category'
    const CATEGORY_TABLE_NAME = 'category';
    //'category-product'
    const CATEGORY_PRODUCT_TABLE_NAME = 'category_products';

    public static $product = ["product" =>
        [
            ["id" => "11", "title" => "Edelweiss", "price" => "200"],
            ["id" => "12", "title" => "Rose", "price" => "50"],
            ["id" => "13", "title" => "Queen Rose 1", "price" => "150"],
            ["id" => "14", "title" => "Queen Violet", "price" => "120"],
            ["id" => "15", "title" => "King Rose 1", "price" => "200"],
            ["id" => "16", "title" => "King Rose 2", "price" => "250"],
        ]];
    public static $category = ["category" =>
        [
            ["id" => "41", "title" => "Miscellaneous"],
            ["id" => "42", "title" => "Flower"],
            ["id" => "43", "title" => "Roses"],
            ["id" => "44", "title" => "King Rose"],
            ["id" => "45", "title" => "Queen Rose"],
        ]];
    public static $categoryProduct = ["category_products" =>
        [
            ["id" => "41", 'category_id' => '41', 'product_id' => '11'],
            ["id" => "42", 'category_id' => '42', 'product_id' => '12'],
            ["id" => "43", 'category_id' => '42', 'product_id' => '13'],
            ["id" => "44", 'category_id' => '42', 'product_id' => '14'],
            ["id" => "45", 'category_id' => '42', 'product_id' => '15'],
            ["id" => "46", 'category_id' => '42', 'product_id' => '16'],
            ["id" => "48", 'category_id' => '43', 'product_id' => '12'],
            ["id" => "49", 'category_id' => '43', 'product_id' => '13'],
            ["id" => "50", 'category_id' => '43', 'product_id' => '15'],
            ["id" => "51", 'category_id' => '43', 'product_id' => '16'],
            ["id" => "52", 'category_id' => '44', 'product_id' => '15'],
            ["id" => "53", 'category_id' => '44', 'product_id' => '16'],
            ["id" => "54", 'category_id' => '45', 'product_id' => '13'],
        ]];
    public static $images = ["images" =>
        [
            ["id" => "21", "image" => "icon1.jpg", "product_id" => "11"],
            ["id" => "22", "image" => "icon2.jpg", "product_id" => "12"],
            ["id" => "23", "image" => "icon3.jpg", "product_id" => "11"],
            ["id" => "24", "image" => "icon4.jpg", "product_id" => "13"],
            ["id" => "25", "image" => "icon5.jpg", "product_id" => "14"],
            ["id" => "26", "image" => "icon6.jpg", "product_id" => "12"],
            ["id" => "27", "image" => "icon6.jpg", "product_id" => "15"],
            ["id" => "28", "image" => "icon7.jpg", "product_id" => "14"],
            ["id" => "29", "image" => "icon7.jpg", "product_id" => "14"],
            ["id" => "30", "image" => "icon7.jpg", "product_id" => "15"],
            ["id" => "31", "image" => "icon8.jpg", "product_id" => "16"],
        ]];

    public static $develop_tables_config = [
        self::PRODUCT_TABLE_NAME => [
            'id' => [
                TableManager::FIELD_TYPE => 'Integer',
                TableManager::FIELD_PARAMS => [
                    'options' => ['autoincrement' => true]
                ]
            ],
            'title' => [
                TableManager::FIELD_TYPE => 'Varchar',
                TableManager::FIELD_PARAMS => [
                    'length' => 100,
                    'nullable' => false,
                ],
            ],
            'price' => [
                TableManager::FIELD_TYPE => 'Decimal',
                TableManager::FIELD_PARAMS => [
                    'nullable' => true,
                    'default' => 0
                ],
            ],
        ],
        self::IMAGE_TABLE_NAME => [
            'id' => [
                TableManager::FIELD_TYPE => 'Integer',
                TableManager::FIELD_PARAMS => [
                    'options' => ['autoincrement' => true]
                ],
            ],
            'image' => [
                TableManager::FIELD_TYPE => 'Varchar',
                TableManager::FIELD_PARAMS => [
                    'length' => 100,
                    'nullable' => false,
                ],
            ],
            'product_id' => [
                TableManager::FIELD_TYPE => 'Integer',
                TableManager::FOREIGN_KEY => [
                    'referenceTable' => Store::PRODUCT_TABLE_NAME,
                    'referenceColumn' => 'id',
                    'onDeleteRule' => 'cascade',
                    'onUpdateRule' => null,
                    'name' => null
                ]
            ]
        ],
        self::CATEGORY_TABLE_NAME => [
            'id' => [
                TableManager::FIELD_TYPE => 'Integer',
                TableManager::FIELD_PARAMS => [
                    'options' => ['autoincrement' => true]
                ]
            ],
            'title' => [
                TableManager::FIELD_TYPE => 'Varchar',
                TableManager::FIELD_PARAMS => [
                    'length' => 100,
                    'nullable' => false,
                ],
            ],
        ],
        self::CATEGORY_PRODUCT_TABLE_NAME => [
            'id' => [
                TableManager::FIELD_TYPE => 'Integer',
                TableManager::FIELD_PARAMS => [
                    'options' => ['autoincrement' => true]
                ]
            ],
            'product_id' => [
                TableManager::FIELD_TYPE => 'Integer',
                TableManager::FOREIGN_KEY => [
                    'referenceTable' => Store::PRODUCT_TABLE_NAME,
                    'referenceColumn' => 'id',
                    'onDeleteRule' => 'cascade',
                    'onUpdateRule' => null,
                    'name' => null
                ]
            ],
            'category_id' => [
                TableManager::FIELD_TYPE => 'Integer',
                TableManager::FOREIGN_KEY => [
                    'referenceTable' => Store::CATEGORY_TABLE_NAME,
                    'referenceColumn' => 'id',
                    'onDeleteRule' => 'cascade',
                    'onUpdateRule' => null,
                    'name' => null
                ]
            ]
        ]

    ];
}