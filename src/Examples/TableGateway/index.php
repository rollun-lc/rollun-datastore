<?php

// try http://__zaboy-rest/api/rest/index_StoreMiddleware?fNumberOfHours=8&fWeekday=Monday
// Change to the project root, to simplify resolving paths
chdir(dirname(__DIR__));
//test_res_http
// Setup autoloading
require 'vendor/autoload.php';

use rollun\datastore\TableGateway\TableManagerMysql;

$container = include 'config/container.php';
$tableName = 'test_create_table';

$adapter = $container->get('db');
$tableManager = new TableManagerMySql($adapter, $tableName);
$tableData = [
    'id' => [
        'field_type' => 'Integer',
        'field_params' => [
            'options' => ['autoincrement' => true]
        ]
    ],
    'name' => [
        'field_type' => 'Varchar',
        'field_params' => [
            'length' => 10,
            'nullable' => true,
            'default' => 'what?'
        ]
    ]
];
$tableManager->createTable($tableData);


$deleteStatementStr = "DROP TABLE IF EXISTS " . $adapter->platform->quoteIdentifier($tableName);
$deleteStatement = $adapter->query($deleteStatementStr);
$deleteStatement->execute();
