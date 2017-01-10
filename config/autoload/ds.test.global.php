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
            'sql' => 'rolluncom\datastore\TableGateway\DbSql\MultiInsertSql',
        ],
    ],

    'dataStore' => [
        'test_DataStoreDbTableWithNameAsResourceName' => [
            'class' => 'rolluncom\datastore\DataStore\DbTable',
            'tableName' => 'table_for_db_data_store'
        ],
        'test_StoreForMiddleware' => [
            'class' => 'rolluncom\datastore\DataStore\Memory',
        ],
        'testDbTable' => [
            'class' => 'rolluncom\datastore\DataStore\DbTable',
            'tableName' => 'test_res_tablle'
        ],

        'testDbTableMultiInsert' => [
            'class' => 'rolluncom\datastore\DataStore\DbTable',
            'tableGateway' => 'test_res_tablle',
        ],
        /*'testHttpClient' => [
            'class' => 'zaboy\rest\DataStore\HttpClient',
            'tableName' => 'test_res_http',
            'url' => 'http://zaboy-rest.loc/api/rest/test_res_http',
            'options' => ['timeout' => 30]
        ],
        'testEavOverHttpClient' => [
            'class' => 'zaboy\rest\DataStore\HttpClient',
            'url' => 'http://zaboy-rest.loc/api/rest/entity_product',
            'options' => ['timeout' => 30]
        ],
         'testEavOverHttpDbClient' => [
            'class' => 'zaboy\rest\DataStore\HttpClient',
            'url' => 'http://localhost:9090/api/rest/db.entity_product',
            'options' => ['timeout' => 30]
        ],
        */
        'testMemory' => [
            'class' => 'rolluncom\datastore\DataStore\Memory',
        ],
        'testCsvBase' => [
            'class' => 'rolluncom\datastore\DataStore\CsvBase',
            'filename' => sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'testCsvBase.tmp',
            'delimiter' => ';',
        ],
        'testCsvIntId' => [
            'class' => 'rolluncom\datastore\DataStore\CsvIntId',
            'filename' => sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'testCsvIntId.tmp',
            'delimiter' => ';',
        ],
        'testAspectAbstract' => [
            'class' => 'rolluncom\datastore\DataStore\Aspect\AspectAbstract',
            'dataStore' => 'testMemory',
        ],
        
        'testDataSourceDb' => [
            'class' => 'rolluncom\datastore\DataSource\DbTableDataSource',
            //'class' => 'zaboy\rest\DataStore\DbTable',
            'tableName' => 'test_res_http'
        ],
        
        'testCacheable' => [
            'class' => 'rolluncom\datastore\DataStore\Cacheable',
            'dataSource' => 'testDataSourceDb',
            'cacheable' => 'testDbTable'
        ]
    ],
    'middleware' => [
        'test_MiddlewareWithNameAsResourceName' => [
            'class' => 'rolluncom\datastore\Middleware\DataStoreRest',
            'dataStore' => 'test_StoreForMiddleware'
        ],
        'MiddlewareMemoryTest' => [
            'class' => 'rolluncom\datastore\Examples\Middleware\DataStoreMemory',
            'dataStore' => 'testMemory'
        ]
    ],
];
