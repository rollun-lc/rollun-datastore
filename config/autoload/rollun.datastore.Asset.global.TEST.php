<?php

$testHost = (string) (getenv('TEST_HOST') ?: 'http://localhost:9000/');
$testHost = rtrim($testHost, '/');

return [
    'dataStore' => [
        'testHttpClient' => [
            'class' => 'rollun\datastore\DataStore\HttpClient',
            'tableName' => 'test_res_http',
            'url' => $testHost . '/api/datastore/testDataSourceDb',
            'options' => [
                'timeout' => 30,
            ],
        ],
    ],
];
