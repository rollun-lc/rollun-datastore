<?php

return [
    'tableManagerMysql' => [
        'tablesConfigs' => [
            'test_table_config' => [],
        ],
        'autocreateTables' => [
            'test_autocreate_table' => 'test_table_config'
        ]
    ],

    'tableGateway' =>[
        'test_res_tablle' => [
            'sql' => 'rollun\datastore\TableGateway\DbSql\MultiInsertSql',
        ],
    ],

    'dataStore' => [
        'test_DataStoreDbTableWithNameAsResourceName' => [
            'class' => 'rollun\datastore\DataStore\DbTable',
            'tableName' => 'table_for_db_data_store'
        ],
        'test_StoreForMiddleware' => [
            'class' => 'rollun\datastore\DataStore\Memory',
        ],
        'testDbTable' => [
            'class' => 'rollun\datastore\DataStore\DbTable',
            'tableName' => 'test_res_tablle'
        ],

        'testDbTableMultiInsert' => [
            'class' => 'rollun\datastore\DataStore\DbTable',
            'tableGateway' => 'test_res_tablle',
        ],
        'testHttpClient' => [
            'class' => 'rollun\datastore\DataStore\HttpClient',
            'tableName' => 'test_res_http',
            'url' => 'http://'. constant("HOST") .'/api/rest/test_res_http',
            'options' => ['timeout' => 30]
        ],
        'testEavOverHttpClient' => [
            'class' => 'rollun\datastore\DataStore\HttpClient',
            'url' => 'http://'. constant("HOST") .'/api/rest/entity_product',
            'options' => ['timeout' => 30]
        ],
        'testEavOverHttpDbClient' => [
            'class' => 'rollun\datastore\DataStore\HttpClient',
            'url' => 'http://'. constant("HOST") .'/api/rest/db~entity_product',
            'options' => ['timeout' => 30]
        ],
        'testMemory' => [
            'class' => 'rollun\datastore\DataStore\Memory',
        ],
        'testCsvBase' => [
            'class' => 'rollun\datastore\DataStore\CsvBase',
            'filename' => sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'testCsvBase.tmp',
            'delimiter' => ';',
        ],
        'testCsvIntId' => [
            'class' => 'rollun\datastore\DataStore\CsvIntId',
            'filename' => sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'testCsvIntId.tmp',
            'delimiter' => ';',
        ],
        'testAspectAbstract' => [
            'class' => 'rollun\datastore\DataStore\Aspect\AspectAbstract',
            'dataStore' => 'testMemory',
        ],
        
        'testDataSourceDb' => [
            'class' => 'rollun\datastore\DataSource\DbTableDataSource',
            //'class' => 'rollun\datastore\DataStore\DbTable',
            'tableName' => 'test_res_http'
        ],
        
        'testCacheable' => [
            'class' => 'rollun\datastore\DataStore\Cacheable',
            'dataSource' => 'testDataSourceDb',
            'cacheable' => 'testDbTable'
        ]
    ],
    'middleware' => [
        'test_MiddlewareWithNameAsResourceName' => [
            'class' => 'rollun\datastore\Middleware\DataStoreRest',
            'dataStore' => 'test_StoreForMiddleware'
        ],
        'MiddlewareMemoryTest' => [
            'class' => 'rollun\datastore\Examples\Middleware\DataStoreMemory',
            'dataStore' => 'testMemory'
        ]
    ],
];
