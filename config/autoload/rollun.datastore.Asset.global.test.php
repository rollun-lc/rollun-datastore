<?php

return [
    'tableGateway' => [
        'table_with_name_same_as_resource_name' => [],
        'tbl_name_which_exist' => [],
        'test_res_http' => [],
        'testTable' => [],
    ],
    'dataStore' => [
        'exploited1DbTable' => [
            'class' => 'rollun\datastore\DataStore\DbTable',
            'tableName' => 'test_exploit1_tablle',
        ],
        'exploited2DbTable' => [
            'class' => 'rollun\datastore\DataStore\DbTable',
            'tableName' => 'test_exploit2_tablle',
        ],
        'test_DataStoreDbTableWithNameAsResourceName' => [
            'class' => 'rollun\datastore\DataStore\DbTable',
            'tableName' => 'table_for_db_data_store',
        ],
        'test_StoreForMiddleware' => [
            'class' => 'rollun\datastore\DataStore\Memory',
        ],
        'testDbTable' => [
            'class' => 'rollun\datastore\DataStore\DbTable',
            'tableName' => 'test_res_tablle',
        ],
        'testDbTableSerialized' => [
            'class' => 'rollun\datastore\DataStore\SerializedDbTable',
            'tableName' => 'test_res_tablle',
            'sqlQueryBuilder' => 'rollun\datastore\TableGateway\SqlQueryBuilder',
        ],
        'testHttpClient' => [
            'class' => 'rollun\datastore\DataStore\HttpClient',
            'tableName' => 'test_res_http',
            'url' => 'http://127.0.0.1:9000/api/datastore/testDataSourceDb',
            'options' => [
                'timeout' => 30,
            ],
        ],
        'testMemory' => [
            'class' => 'rollun\datastore\DataStore\Memory',
        ],
        'testCsvBase' => [
            'class' => 'rollun\datastore\DataStore\CsvBase',
            'filename' => '/tmp/testCsvBase.tmp',
            'delimiter' => ';',
        ],
        'testCsvIntId' => [
            'class' => 'rollun\datastore\DataStore\CsvIntId',
            'filename' => '/tmp/testCsvIntId.tmp',
            'delimiter' => ';',
        ],
        'testAspectAbstract' => [
            'class' => 'rollun\datastore\DataStore\Aspect\AspectAbstract',
            'dataStore' => 'testMemory',
        ],
        'testDataSourceDb' => [
            'class' => 'rollun\datastore\DataSource\DbTableDataSource',
            'tableName' => 'test_res_http',
        ],
        'testCacheable' => [
            'class' => 'rollun\datastore\DataStore\Cacheable',
            'dataSource' => 'testDataSourceDb',
            'cacheable' => 'testDbTable',
        ],
        'memoryDataStore' => [
            'class' => 'rollun\datastore\DataStore\Memory',
        ],
        'dbDataStore' => [
            'class' => 'rollun\datastore\DataStore\DbTable',
            'tableGateway' => 'testTable',
        ],
        'dbDataStoreSerialized' => [
            'class' => 'rollun\datastore\DataStore\SerializedDbTable',
            'tableName' => 'testTable',
            'sqlQueryBuilder' => 'sqlQueryBuilder1',
        ],
    ],
    'rollun\datastore\TableGateway\Factory\SqlQueryBuilderAbstractFactory' => [
        'sqlQueryBuilder1' => [
            'tableName' => 'test_res_tablle',
            'sqlConditionBuilder' => 'sqlConditionBuilder1',
        ],
        'rollun\datastore\TableGateway\SqlQueryBuilder' => [
            'tableName' => 'testTable',
            'sqlConditionBuilder' => 'rollun\datastore\DataStore\ConditionBuilder\SqlConditionBuilder',
        ],
    ],
    'rollun\datastore\DataStore\ConditionBuilder\SqlConditionBuilderAbstractFactory' => [
        'sqlConditionBuilder1' => [
            'tableName' => 'test_res_tablle',
        ],
        'rollun\datastore\DataStore\ConditionBuilder\SqlConditionBuilder' => [
            'tableName' => 'testTable',
        ],
    ],
    'tableManagerMysql' => [
        'tablesConfigs' => [
            'test_table_config' => [],
        ],
        'autocreateTables' => [
            'test_autocreate_table' => 'test_table_config',
        ],
    ],
];
q