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
        ],
        'testHttpClient' => [
            'class' => 'rollun\datastore\DataStore\HttpClient',
            'tableName' => 'test_res_http',
            'url' => 'http://localhost:9000/api/datastore/testDataSourceDb',
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
        ],
        'testDataStoreAspect1' => [
            'class'     => \rollun\datastore\DataStore\Aspect\AspectWithEventManagerAbstract::class,
            'dataStore' => 'testDataStore1',
            'listeners' => [
                'onPostUpdate' => [function (\Zend\EventManager\Event $event) {
                    file_put_contents('test_on_post_create.json', json_encode($event->getParam('result')));
                }],
            ]
        ],
        'testDataStore1'       => [
            'class'           => \rollun\datastore\DataStore\Memory::class,
            'requiredColumns' => ['id', 'name']
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
