# rollun-datastore - Техническая документация

## Обзор библиотеки

`rollun-datastore` - это PHP библиотека, предоставляющая единый интерфейс для работы с различными хранилищами данных на основе [Resource Query Language (RQL)](https://www.sitepen.com/blog/2010/11/02/resource-query-language-a-query-language-for-the-web-nosql/).

### Основные возможности

- **Унифицированный API** для работы с различными хранилищами данных
- **RQL поддержка** - мощный язык запросов для фильтрации, сортировки и агрегации
- **Множественные реализации** DataStore (БД, CSV, HTTP, Memory)
- **Middleware архитектура** для HTTP API
- **Repository паттерн** для работы с моделями
- **Загрузка данных** через Uploader компонент

### Поддерживаемые хранилища

#### Основные DataStore реализации:
1. **DbTable** (`rollun\datastore\DataStore\DbTable`) - таблицы базы данных (MySQL, PostgreSQL, SQLite)
   - Свойства: `$dbTable` (TableGateway), `$sqlQueryBuilder` (SqlQueryBuilder), `$writeLogs` (bool), `$loggerService` (LoggerInterface)
   - Конструктор: `__construct(TableGateway $dbTable, bool $writeLogs = false, ?LoggerInterface $loggerService = null)`

2. **Memory** (`rollun\datastore\DataStore\Memory`) - хранение в оперативной памяти
   - Свойства: `$items` (array), `$columns` (array)
   - Конструктор: `__construct(array $columns = [])`

3. **HttpClient** (`rollun\datastore\DataStore\HttpClient`) - внешние HTTP API
   - Свойства: `$url`, `$login`, `$password`, `$client` (Laminas\Http\Client), `$options`, `$lifeCycleToken`, `$identifier`
   - Конструктор: `__construct(Client $client, $url, $options = [], LifeCycleToken $lifeCycleToken = null)`

4. **CsvBase** (`rollun\datastore\DataStore\CsvBase`) - CSV файлы
   - Константы: `MAX_FILE_SIZE_FOR_CACHE = 1048576`, `MAX_LOCK_TRIES = 10`, `DEFAULT_DELIMITER = ','`
   - Свойства: `$csvDelimiter` (string), `$columns` (array), `$file` (SplFileObject)
   - Конструктор: `__construct($filename, $csvDelimiter = null)`

5. **CsvIntId** (`rollun\datastore\DataStore\CsvIntId`) - CSV с автогенерацией ID
   - Наследует: `CsvBase`
   - Конструктор: `__construct($filename, $delimiter)`

6. **SerializedDbTable** (`rollun\datastore\DataStore\SerializedDbTable`) - сериализованные данные в БД
   - Наследует: `DbTable`
   - Свойства: `$tableName`
   - Методы: `__sleep()`, `__wakeup()`

7. **Cacheable** (`rollun\datastore\DataStore\Cacheable`) - кэшируемые DataStore
   - Реализует: `DataStoresInterface`, `RefreshableInterface`
   - Свойства: `$cashStore` (DataStoresInterface), `$dataSource` (DataSourceInterface)
   - Конструктор: `__construct(DataSourceInterface $dataSource, DataStoresInterface $cashStore = null)`

#### Aspect DataStore реализации:
8. **AspectAbstract** (`rollun\datastore\DataStore\Aspect\AspectAbstract`) - базовый аспект
9. **AspectWithEventManagerAbstract** (`rollun\datastore\DataStore\Aspect\AspectWithEventManagerAbstract`) - аспект с EventManager
10. **AspectEntityMapper** (`rollun\datastore\DataStore\Aspect\AspectEntityMapper`) - маппинг сущностей
11. **AspectModifyTable** (`rollun\datastore\DataStore\Aspect\AspectModifyTable`) - модификация таблиц
12. **AspectReadOnly** (`rollun\datastore\DataStore\Aspect\AspectReadOnly`) - только для чтения
13. **AspectSchema** (`rollun\datastore\DataStore\Aspect\AspectSchema`) - работа со схемами
14. **AspectTyped** (`rollun\datastore\DataStore\Aspect\AspectTyped`) - типизированные данные

### Архитектура

Библиотека состоит из трех основных пакетов:

- `rollun\datastore` - основная функциональность DataStore
- `rollun\repository` - Repository паттерн для моделей
- `rollun\uploader` - загрузка данных

### Требования

- PHP 8.0+
- PDO extension
- JSON extension
- Laminas компоненты (ServiceManager, DB, HTTP, etc.)

### Установка

```bash
composer require rollun-com/rollun-datastore
```

## Структура документации

- [Архитектура](architecture.md) - детальное описание архитектуры
- [API Reference](api_reference.md) - полное описание API
- [Конфигурация](configuration.md) - настройка и конфигурация
- [Примеры использования](examples.md) - практические примеры
- [Troubleshooting](TROUBLESHOOTING.md) - решение проблем

## Быстрый старт

### Базовое использование DataStore

```php
use rollun\datastore\DataStore\Memory;

// Создание DataStore в памяти
$dataStore = new Memory(['id', 'name', 'email']);

// Создание записи
$record = $dataStore->create([
    'id' => 1,
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// Чтение записи
$record = $dataStore->read(1);

// Поиск с RQL
$query = new \Xiag\Rql\Parser\Query();
$query->setQuery(new \Xiag\Rql\Parser\Node\Query\ScalarOperator\EqNode('name', 'John Doe'));
$results = $dataStore->query($query);
```

### HTTP API

```php
use rollun\datastore\Middleware\DataStoreApi;
use rollun\datastore\Middleware\Determinator;

// Создание middleware для HTTP API
$determinator = new Determinator($dataStorePluginManager);
$api = new DataStoreApi($determinator);

// Обработка HTTP запросов
$response = $api->process($request, $handler);
```

### Repository паттерн

```php
use rollun\repository\ModelRepository;
use rollun\repository\Model\SimpleModelExtendedAbstract;

// Создание репозитория
$repository = new ModelRepository(
    $dataStore,
    SimpleModelExtendedAbstract::class,
    $mapper,
    $logger
);

// Работа с моделями
$model = $repository->findById(1);
$model->name = 'Updated Name';
$repository->save($model);
```

## Особенности RQL

Библиотека использует расширенную версию `rawurlencode` с дополнительными преобразованиями:

- `-` => `%2D`
- `_` => `%5F`
- `.` => `%2E`
- `~` => `%7E`

## Лицензия

Proprietary License

## Авторы

- avz-cmf (email@example.com)
- victorynox (it.proffesor02@gmail.com)
