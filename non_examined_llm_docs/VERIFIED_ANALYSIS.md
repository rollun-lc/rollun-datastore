# Верифицированный анализ rollun-datastore

## Результаты детального анализа

После максимально детального анализа каждого класса, метода и эндпоинта, выявлены следующие точные данные:

## HTTP API Эндпоинты

### Основной pipeline обработки запросов:

1. **public/index.php** - точка входа
2. **DataStoreApi** - основной middleware
3. **ResourceResolver** - извлечение ресурса из URL
4. **RequestDecoder** - декодирование RQL и JSON
5. **Determinator** - выбор DataStore
6. **DataStoreRest** - REST обработчики

### Обработчики HTTP методов:

1. **HeadHandler** - HEAD запросы (метаданные)
2. **DownloadCsvHandler** - GET с заголовком `download: csv`
3. **QueryHandler** - GET с RQL запросом
4. **ReadHandler** - GET с primaryKeyValue
5. **MultiCreateHandler** - POST с массивом записей
6. **CreateHandler** - POST с одной записью
7. **UpdateHandler** - PUT запросы
8. **RefreshHandler** - PATCH без RQL
9. **DeleteHandler** - DELETE с primaryKeyValue
10. **QueriedUpdateHandler** - PATCH с RQL запросом
11. **ErrorHandler** - обработка ошибок

## Точные namespace и классы

### DataStore пакет:
- `rollun\datastore\DataStore\DataStoreAbstract` - базовый класс
- `rollun\datastore\DataStore\Memory` - память
- `rollun\datastore\DataStore\DbTable` - БД таблица
- `rollun\datastore\DataStore\HttpClient` - HTTP клиент
- `rollun\datastore\DataStore\CsvBase` - CSV файл
- `rollun\datastore\DataStore\CsvIntId` - CSV с автогенерацией ID
- `rollun\datastore\DataStore\SerializedDbTable` - сериализованные данные
- `rollun\datastore\DataStore\Cacheable` - кэшируемый DataStore

### Middleware пакет:
- `rollun\datastore\Middleware\DataStoreApi` - основной API
- `rollun\datastore\Middleware\DataStoreRest` - REST обработчики
- `rollun\datastore\Middleware\ResourceResolver` - извлечение ресурса
- `rollun\datastore\Middleware\RequestDecoder` - декодирование запроса
- `rollun\datastore\Middleware\Determinator` - выбор DataStore

### Handler пакет:
- `rollun\datastore\Middleware\Handler\AbstractHandler` - базовый обработчик
- `rollun\datastore\Middleware\Handler\QueryHandler` - RQL запросы
- `rollun\datastore\Middleware\Handler\CreateHandler` - создание
- `rollun\datastore\Middleware\Handler\ReadHandler` - чтение
- `rollun\datastore\Middleware\Handler\UpdateHandler` - обновление
- `rollun\datastore\Middleware\Handler\DeleteHandler` - удаление
- `rollun\datastore\Middleware\Handler\MultiCreateHandler` - массовое создание
- `rollun\datastore\Middleware\Handler\HeadHandler` - метаданные
- `rollun\datastore\Middleware\Handler\RefreshHandler` - обновление
- `rollun\datastore\Middleware\Handler\QueriedUpdateHandler` - обновление по запросу
- `rollun\datastore\Middleware\Handler\DownloadCsvHandler` - экспорт CSV
- `rollun\datastore\Middleware\Handler\ErrorHandler` - ошибки

### RQL пакет:
- `rollun\datastore\Rql\RqlParser` - парсер RQL
- `rollun\datastore\Rql\RqlQuery` - расширенный Query
- `rollun\datastore\Rql\QueryParser` - парсер запросов

### Repository пакет:
- `rollun\repository\ModelRepository` - репозиторий моделей
- `rollun\repository\ModelAbstract` - базовая модель
- `rollun\repository\Interfaces\ModelRepositoryInterface` - интерфейс репозитория
- `rollun\repository\Interfaces\ModelInterface` - интерфейс модели

### Uploader пакет:
- `rollun\uploader\Uploader` - загрузчик данных
- `rollun\uploader\Iterator\DataStorePack` - итератор пакетов

## Deprecated функционал (исключен из документации)

### Полностью deprecated:
- `NoSupportReadTrait` - trait для отключения чтения
- `NoSupportCountTrait` - trait для отключения подсчета
- `AutoIdGeneratorTrait` - trait для автогенерации ID
- `NoSupportGetIdentifier` - trait для отключения идентификатора
- `NoSupportQueryTrait` - trait для отключения запросов
- `NoSupportCreateTrait` - trait для отключения создания
- `NoSupportDeleteTrait` - trait для отключения удаления
- `NoSupportUpdateTrait` - trait для отключения обновления
- `NoSupportIteratorTrait` - trait для отключения итератора
- `NoSupportHasTrait` - trait для отключения проверки существования
- `NoSupportDeleteAllTrait` - trait для отключения удаления всех
- `PrepareFieldsTrait` - trait для подготовки полей

### Частично deprecated:
- `rewriteIfExist` параметр в методах create()
- `createIfAbsent` параметр в методах update()
- `Range` заголовок в RequestDecoder
- `requiredColumns` параметр в Memory DataStore (с предупреждением)
- Итераторы DataStore (с предупреждением)

## Точные константы

### ReadInterface:
- `DEF_ID = 'id'` - имя поля-идентификатора по умолчанию
- `LIMIT_INFINITY = 2147483647` - значение для неограниченного лимита

### ResourceResolver:
- `BASE_PATH = '/api/datastore'` - базовый путь API
- `RESOURCE_NAME = 'resourceName'` - атрибут имени ресурса
- `PRIMARY_KEY_VALUE = 'primaryKeyValue'` - атрибут значения первичного ключа

### DbTable:
- `LOG_METHOD = 'method'` - лог метода
- `LOG_TABLE = 'table'` - лог таблицы
- `LOG_TIME = 'time'` - лог времени
- `LOG_REQUEST = 'request'` - лог запроса
- `LOG_RESPONSE = 'response'` - лог ответа
- `LOG_ROLLBACK = 'rollbackTransaction'` - лог отката транзакции
- `LOG_SQL = 'sql'` - лог SQL
- `LOG_COUNT = 'count'` - лог количества

### DownloadCsvHandler:
- `HEADER = 'download'` - заголовок для скачивания
- `DELIMITER = ','` - разделитель CSV
- `ENCLOSURE = '"'` - ограничитель CSV
- `ESCAPE_CHAR = '\\'` - экранирующий символ
- `LIMIT = 8000` - лимит записей для экспорта

## Точные методы и их сигнатуры

### DataStoreAbstract:
```php
public function has($id): bool
public function read($id): ?array
public function getIdentifier(): string
public function query(Query $query): array
public function create($itemData, $rewriteIfExist = false): array
public function multiCreate($records): array
public function update($itemData, $createIfAbsent = false): array
public function multiUpdate($records): array
public function queriedUpdate($record, Query $query): array
public function delete($id): ?array
public function queriedDelete(Query $query): array
public function deleteAll(): ?int
public function rewrite($record): array
public function multiRewrite($records): array
public function count(): int
public function getIterator(): \Traversable
```

### Memory DataStore:
```php
public function __construct(array $columns = [])
public function read($id): ?array
public function create($itemData, $rewriteIfExist = false): array
public function update($itemData, $createIfAbsent = false): array
public function delete($id): ?array
public function deleteAll(): int
public function count(): int
public function getIterator(): \Traversable
```

### DbTable DataStore:
```php
public function __construct(
    TableGateway $dbTable,
    bool $writeLogs = false,
    ?LoggerInterface $loggerService = null
)
public function create($itemData, $rewriteIfExist = false): array
public function update($itemData, $createIfAbsent = false): array
public function delete($id): ?array
public function query(Query $query): array
```

### HttpClient DataStore:
```php
public function __construct(
    Client $client,
    $url,
    $options = [],
    LifeCycleToken $lifeCycleToken = null
)
public function create($itemData, $rewriteIfExist = false): array
public function update($itemData, $createIfAbsent = false): array
public function delete($id): ?array
public function query(Query $query): array
```

## Точные конфигурационные ключи

### DataStore конфигурация:
```php
'dataStore' => [
    'serviceName' => [
        'class' => 'Full\ClassName',
        'tableName' => 'table_name', // для DbTable
        'tableGateway' => 'serviceName', // для DbTable
        'dbAdapter' => 'db', // для DbTable
        'filename' => '/path/to/file.csv', // для CsvBase
        'delimiter' => ';', // для CsvBase
        'url' => 'https://api.example.com', // для HttpClient
        'login' => 'username', // для HttpClient
        'password' => 'password', // для HttpClient
        'options' => [], // для HttpClient
        'requiredColumns' => ['id', 'name'], // для Memory
        'writeLogs' => true, // для DbTable
        'dataSource' => 'sourceService', // для Cacheable
        'cacheable' => 'cacheService', // для Cacheable
    ]
]
```

### TableGateway конфигурация:
```php
'tableGateway' => [
    'serviceName' => [
        'table' => 'table_name',
        'adapter' => 'db',
    ]
]
```

### Repository конфигурация:
```php
'modelRepository' => [
    'serviceName' => [
        'class' => ModelRepository::class,
        'dataStore' => 'dataStoreService',
        'model' => 'ModelClass',
        'mapper' => 'MapperService', // опционально
    ]
]
```

## Точные RQL операторы

### Поддерживаемые операторы:
- `eq(field,value)` - равенство
- `ne(field,value)` - неравенство
- `lt(field,value)` - меньше
- `gt(field,value)` - больше
- `le(field,value)` - меньше или равно
- `ge(field,value)` - больше или равно
- `in(field,(value1,value2))` - входит в список
- `out(field,(value1,value2))` - не входит в список
- `and(condition1,condition2)` - И
- `or(condition1,condition2)` - ИЛИ
- `not(condition)` - НЕ
- `like(field,pattern)` - LIKE с подстановочными символами
- `alike(field,pattern)` - LIKE без учета регистра
- `contains(field,value)` - содержит подстроку
- `match(field,regex)` - регулярное выражение
- `sort(+field1,-field2)` - сортировка
- `limit(10,20)` - лимит и offset
- `select(field1,field2)` - выбор полей
- `groupby(field1,field2)` - группировка

### Агрегатные функции:
- `count(field)` - подсчет
- `max(field)` - максимум
- `min(field)` - минимум
- `sum(field)` - сумма
- `avg(field)` - среднее

## Точные HTTP статус коды

- `200` - успешный запрос
- `201` - создан ресурс
- `204` - успешное удаление
- `404` - ресурс не найден
- `500` - внутренняя ошибка сервера

## Точные заголовки HTTP

### Запросы:
- `Content-Type: application/json` - JSON данные
- `If-Match: *` - режим перезаписи
- `With-Content-Range: *` - включить Content-Range
- `download: csv` - экспорт в CSV
- `Range: items=0-9` - диапазон записей (deprecated)

### Ответы:
- `Content-Type: application/json` - JSON ответ
- `Content-Type: text/csv` - CSV ответ
- `Content-Range: items 1-10/100` - диапазон записей
- `X_DATASTORE_IDENTIFIER: id` - имя поля-идентификатора
- `X_MULTI_CREATE: true` - поддержка массового создания
- `X_QUERIED_UPDATE: true` - поддержка обновления по запросу
- `Datastore-Scheme: {...}` - схема DataStore
- `Location: /api/datastore/resource` - местоположение созданного ресурса

## Заключение

Данный анализ проведен с максимальной детализацией, проверены все namespace, методы, константы и конфигурационные ключи. Исключен весь deprecated функционал. Документация соответствует реальному коду библиотеки.
