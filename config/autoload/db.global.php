<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

use Laminas\Db\Adapter\AdapterInterface;

return [
    'dependencies' => [
        'aliases' => [
            'db' => AdapterInterface::class,
        ],
    ],
    'db' => [
        'driver' => getenv('DB_DRIVER') ?: 'Pdo_Mysql',
        'database' => getenv('DB_NAME'),
        'username' => getenv('DB_USER'),
        'password' => getenv('DB_PASS'),
        'hostname' => getenv('DB_HOST'),
        'port' => getenv('DB_PORT') ?: 3306,
        'adapters' => [
            'db.pdo.wrong-connection' => [
                'driver' => 'Pdo_Mysql',
                'database' => getenv('DB_NAME'),
                'username' => getenv('DB_USER'),
                'password' => 'wrong',
                'hostname' => getenv('DB_HOST'),
                'port' => getenv('DB_PORT') ?: 3306,
            ],
            'db.mysqli.wrong-connection' => [
                'driver' => 'Mysqli',
                'database' => getenv('DB_NAME'),
                'username' => getenv('DB_USER'),
                'password' => 'wrong',
                'hostname' => getenv('DB_HOST'),
                'port' => getenv('DB_PORT') ?: 3306,
            ],
            'db.mysqli.timeout-1-sec' => [
                'driver' => 'Mysqli',
                'database' => getenv('DB_NAME'),
                'username' => getenv('DB_USER'),
                'password' => getenv('DB_PASS'),
                'hostname' => getenv('DB_HOST'),
                'port' => getenv('DB_PORT') ?: 3306,
                'options' => [
                    'buffer_results' => true,
                ],
                'driver_options' => [
                    MYSQLI_OPT_READ_TIMEOUT => 1,
                ],
            ],
        ],
    ],
];
