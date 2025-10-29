# Конфигурация rollun-datastore

## Обзор конфигурации

Библиотека `rollun-datastore` использует Laminas ServiceManager для управления зависимостями и конфигурацией. Конфигурация разделена на несколько уровней:

1. **ConfigProvider** - конфигурация каждого пакета
2. **Abstract Factories** - фабрики для создания объектов
3. **Global конфигурация** - общие настройки
4. **Environment конфигурация** - настройки для окружения

## Структура конфигурации

### Основные файлы

- `config/config.php` - главный файл конфигурации
- `config/container.php` - DI контейнер
- `config/autoload/*.global.php` - глобальные настройки
- `config/autoload/*.global.dev.php` - настройки для разработки
- `config/autoload/*.test.php` - настройки для тестов

### ConfigProvider структура

Каждый пакет имеет свой ConfigProvider:

```php
class ConfigProvider
{
    public function __invoke()
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }
    
    public function getDependencies()
    {
        return [
            'aliases' => [...],
            'factories' => [...],
            'abstract_factories' => [...],
        ];
    }
}
```

## DataStore конфигурация

### Основная конфигурация

```php
// config/autoload/rollun.datastore.Asset.global.php
return [
    'dataStore' => [
        'myDataStore' => [
            'class' => 'rollun\datastore\DataStore\DbTable',
            'tableName' => 'my_table',
        ],
        'memoryStore' => [
            'class' => 'rollun\datastore\DataStore\Memory',
            'requiredColumns' => ['id', 'name', 'email'],
        ],
        'csvStore' => [
            'class' => 'rollun\datastore\DataStore\CsvBase',
            'filename' => '/path/to/file.csv',
            'delimiter' => ';',
        ],
        'httpStore' => [
            'class' => 'rollun\datastore\DataStore\HttpClient',
            'tableName' => 'api_resource',
            'url' => 'https://api.example.com/datastore',
            'options' => [
                'timeout' => 30,
                'maxredirects' => 5,
            ],
        ],
    ],
];
```

### DbTable конфигурация

```php
'dataStore' => [
    'dbTableStore' => [
        'class' => 'rollun\datastore\DataStore\DbTable',
        'tableGateway' => 'myTableGateway', // Имя TableGateway сервиса
        // ИЛИ
        'tableName' => 'my_table', // Прямое указание таблицы
    ],
],

// TableGateway конфигурация
'tableGateway' => [
    'myTableGateway' => [
        // Конфигурация TableGateway
    ],
],
```

### Memory конфигурация

```php
'dataStore' => [
    'memoryStore' => [
        'class' => 'rollun\datastore\DataStore\Memory',
        'requiredColumns' => ['id', 'name', 'email'], // Обязательные поля
    ],
],
```

### CsvBase конфигурация

```php
'dataStore' => [
    'csvStore' => [
        'class' => 'rollun\datastore\DataStore\CsvBase',
        'filename' => '/path/to/file.csv',
        'delimiter' => ';', // Разделитель полей (по умолчанию ';')
    ],
],
```

### CsvIntId конфигурация

```php
'dataStore' => [
    'csvIntIdStore' => [
        'class' => 'rollun\datastore\DataStore\CsvIntId',
        'filename' => '/path/to/file.csv',
        'delimiter' => ';',
    ],
],
```

### HttpClient конфигурация

```php
'dataStore' => [
    'httpStore' => [
        'class' => 'rollun\datastore\DataStore\HttpClient',
        'tableName' => 'api_resource',
        'url' => 'https://api.example.com/datastore',
        'login' => 'username', // Для Basic Auth
        'password' => 'password', // Для Basic Auth
        'options' => [
            'timeout' => 30,
            'maxredirects' => 5,
            'useragent' => 'MyApp/1.0',
            'adapter' => 'Laminas\Http\Client\Adapter\Curl',
            'curloptions' => [
                CURLOPT_SSL_VERIFYPEER => false,
            ],
        ],
    ],
],
```

### SerializedDbTable конфигурация

```php
'dataStore' => [
    'serializedStore' => [
        'class' => 'rollun\datastore\DataStore\SerializedDbTable',
        'tableName' => 'serialized_data',
    ],
],
```

### Cacheable конфигурация

```php
'dataStore' => [
    'cacheableStore' => [
        'class' => 'rollun\datastore\DataStore\Cacheable',
        'dataSource' => 'sourceDataStore', // Источник данных
        'cacheable' => 'cacheDataStore', // Кэшируемый DataStore
    ],
],

// DataSource конфигурация
'dataSource' => [
    'sourceDataStore' => [
        'class' => 'rollun\datastore\DataSource\DbTableDataSource',
        'tableName' => 'source_table',
    ],
],
```

## Repository конфигурация

### ModelRepository конфигурация

```php
// config/autoload/rollun.model.modelRepository.test.php
return [
    ModelRepositoryAbstractFactory::KEY_MODEL_REPOSITORY => [
        'myModelRepository' => [
            ModelRepositoryAbstractFactory::KEY_CLASS => ModelRepository::class,
            ModelRepositoryAbstractFactory::KEY_DATASTORE => 'myDataStore',
            ModelRepositoryAbstractFactory::KEY_MODEL => MyModel::class,
            ModelRepositoryAbstractFactory::KEY_MAPPER => MyFieldMapper::class, // Опционально
        ],
    ],
];
```

### Модель конфигурация

```php
class MyModel extends SimpleModelExtendedAbstract
{
    protected $fillable = ['name', 'email', 'phone'];
    protected $hidden = ['password'];
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'is_active' => 'boolean',
    ];
}
```

## Uploader конфигурация

### Uploader конфигурация

```php
return [
    UploaderAbstractFactory::KEY => [
        'myUploader' => [
            UploaderAbstractFactory::KEY_SOURCE_DATA_ITERATOR_AGGREGATOR => 'sourceIterator',
            UploaderAbstractFactory::KEY_DESTINATION_DATA_STORE => 'destinationDataStore',
        ],
    ],
];
```

## Database конфигурация

### Основная конфигурация БД

```php
// config/autoload/db.global.php
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
    ],
];
```

### TableGateway конфигурация

```php
'tableGateway' => [
    'myTable' => [
        // Автоматически создается TableGateway для таблицы 'my_table'
    ],
    'customTable' => [
        'table' => 'custom_table_name',
        'adapter' => 'db', // Имя адаптера БД
    ],
],
```

### TableManagerMysql конфигурация

```php
'tableManagerMysql' => [
    'tablesConfigs' => [
        'my_table_config' => [
            'id' => [
                'type' => 'INT',
                'options' => ['AUTO_INCREMENT', 'PRIMARY KEY'],
            ],
            'name' => [
                'type' => 'VARCHAR(255)',
                'options' => ['NOT NULL'],
            ],
            'email' => [
                'type' => 'VARCHAR(255)',
                'options' => ['UNIQUE', 'NOT NULL'],
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'options' => ['DEFAULT CURRENT_TIMESTAMP'],
            ],
        ],
    ],
    'autocreateTables' => [
        'my_table' => 'my_table_config',
    ],
],
```

## Middleware конфигурация

### DataStoreApi конфигурация

```php
'dependencies' => [
    'factories' => [
        DataStoreApi::class => DataStoreApiFactory::class,
        Determinator::class => DeterminatorFactory::class,
        ResourceResolver::class => InvokableFactory::class,
        RequestDecoder::class => InvokableFactory::class,
    ],
],
```

### DataStorePluginManager конфигурация

```php
'dependencies' => [
    'factories' => [
        DataStorePluginManager::class => DataStorePluginManagerFactory::class,
    ],
],
```

## RQL конфигурация

### RqlParser конфигурация

```php
'dependencies' => [
    'factories' => [
        RqlParser::class => function($container) {
            return new RqlParser(
                ['count', 'max', 'min', 'sum', 'avg'], // Разрешенные агрегатные функции
                $container->get(SqlConditionBuilder::class) // ConditionBuilder
            );
        },
    ],
],
```

### ConditionBuilder конфигурация

```php
'dependencies' => [
    'factories' => [
        SqlConditionBuilder::class => SqlConditionBuilderAbstractFactory::class,
        PhpConditionBuilder::class => InvokableFactory::class,
        RqlConditionBuilder::class => InvokableFactory::class,
    ],
],
```

## Логирование конфигурация

### Logger конфигурация

```php
'dependencies' => [
    'factories' => [
        LoggerInterface::class => function($container) {
            return new Logger('datastore');
        },
    ],
],
```

### DataStore с логированием

```php
'dataStore' => [
    'loggedDbTable' => [
        'class' => 'rollun\datastore\DataStore\DbTable',
        'tableName' => 'my_table',
        'writeLogs' => true,
        'loggerService' => LoggerInterface::class,
    ],
],
```

## Кэширование конфигурация

### Cache конфигурация

```php
'dependencies' => [
    'aliases' => [
        'cache' => CacheInterface::class,
    ],
    'factories' => [
        CacheInterface::class => function($container) {
            return new FilesystemCache([
                'cache_dir' => 'data/cache',
                'ttl' => 3600,
            ]);
        },
    ],
],
```

## Environment переменные

### .env файл

```bash
# Database
DB_DRIVER=Pdo_Mysql
DB_NAME=my_database
DB_USER=my_user
DB_PASS=my_password
DB_HOST=localhost
DB_PORT=3306

# Application
APP_ENV=dev
APP_DEBUG=true

# Logging
LOG_LEVEL=debug
LOG_FILE=logs/app.log
```

### Использование в конфигурации

```php
return [
    'db' => [
        'driver' => getenv('DB_DRIVER') ?: 'Pdo_Mysql',
        'database' => getenv('DB_NAME'),
        'username' => getenv('DB_USER'),
        'password' => getenv('DB_PASS'),
        'hostname' => getenv('DB_HOST'),
        'port' => getenv('DB_PORT') ?: 3306,
    ],
];
```

## Производственная конфигурация

### Оптимизации для продакшена

```php
// config/autoload/rollun.datastore.Asset.global.prod.php
return [
    'dataStore' => [
        'productionStore' => [
            'class' => 'rollun\datastore\DataStore\DbTable',
            'tableName' => 'production_table',
            'writeLogs' => false, // Отключить логирование в продакшене
        ],
    ],
    'cache' => [
        'enabled' => true,
        'ttl' => 3600,
    ],
];
```

### Безопасность

```php
return [
    'dataStore' => [
        'secureStore' => [
            'class' => 'rollun\datastore\DataStore\HttpClient',
            'url' => 'https://secure-api.example.com',
            'options' => [
                'timeout' => 30,
                'sslverifypeer' => true,
                'sslverifyhost' => 2,
            ],
        ],
    ],
];
```

## Тестовая конфигурация

### Конфигурация для тестов

```php
// config/autoload/rollun.datastore.Asset.global.test.php
return [
    'dataStore' => [
        'testStore' => [
            'class' => 'rollun\datastore\DataStore\Memory',
            'requiredColumns' => ['id', 'name'],
        ],
    ],
    'db' => [
        'driver' => 'Pdo_Sqlite',
        'database' => ':memory:',
    ],
];
```

## Валидация конфигурации

### Проверка конфигурации

```php
use Laminas\ConfigAggregator\ConfigAggregator;

$config = new ConfigAggregator([
    // ... config providers
]);

$mergedConfig = $config->getMergedConfig();

// Валидация обязательных параметров
if (!isset($mergedConfig['dataStore'])) {
    throw new \InvalidArgumentException('dataStore configuration is required');
}

// Валидация конкретного DataStore
if (isset($mergedConfig['dataStore']['myStore'])) {
    $storeConfig = $mergedConfig['dataStore']['myStore'];
    if (!isset($storeConfig['class'])) {
        throw new \InvalidArgumentException('class is required for DataStore configuration');
    }
}
```

## Динамическая конфигурация

### Создание DataStore программно

```php
use rollun\datastore\DataStore\Memory;
use rollun\datastore\DataStore\DbTable;
use Laminas\Db\TableGateway\TableGateway;

// Memory DataStore
$memoryStore = new Memory(['id', 'name', 'email']);

// DbTable DataStore
$tableGateway = new TableGateway('my_table', $dbAdapter);
$dbStore = new DbTable($tableGateway, true, $logger);

// Регистрация в контейнере
$container->setService('myDynamicStore', $memoryStore);
```

### Изменение конфигурации во время выполнения

```php
// Получение текущей конфигурации
$config = $container->get('config');

// Добавление нового DataStore
$config['dataStore']['dynamicStore'] = [
    'class' => 'rollun\datastore\DataStore\Memory',
    'requiredColumns' => ['id', 'name'],
];

// Обновление конфигурации
$container->setService('config', $config);
```
