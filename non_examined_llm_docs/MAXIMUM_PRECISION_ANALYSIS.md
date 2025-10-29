# Максимально точный анализ rollun-datastore

## Критическая проверка и исправления

После критической проверки выявлены неточности и расплывчатости в предыдущей документации. Данный анализ содержит максимально точную информацию, соответствующую реальному коду.

## Точные DataStore реализации

### Основные DataStore классы (namespace: `rollun\datastore\DataStore`):

1. **DataStoreAbstract** - абстрактный базовый класс
   - Реализует: `DataStoresInterface`, `DataStoreInterface`
   - Содержит: `$conditionBuilder` (ConditionBuilderAbstract)
   - Методы: все CRUD операции + query, count, getIterator

2. **Memory** - хранение в оперативной памяти
   - Наследует: `DataStoreAbstract`
   - Свойства: `$items` (array), `$columns` (array)
   - Конструктор: `__construct(array $columns = [])`

3. **DbTable** - таблицы базы данных
   - Наследует: `DataStoreAbstract`
   - Свойства: `$dbTable` (TableGateway), `$sqlQueryBuilder` (SqlQueryBuilder), `$writeLogs` (bool), `$loggerService` (LoggerInterface)
   - Конструктор: `__construct(TableGateway $dbTable, bool $writeLogs = false, ?LoggerInterface $loggerService = null)`

4. **HttpClient** - внешние HTTP API
   - Наследует: `DataStoreAbstract`
   - Свойства: `$url`, `$login`, `$password`, `$client` (Laminas\Http\Client), `$options`, `$lifeCycleToken`, `$identifier`
   - Конструктор: `__construct(Client $client, $url, $options = [], LifeCycleToken $lifeCycleToken = null)`

5. **CsvBase** - CSV файлы
   - Наследует: `DataStoreAbstract`
   - Константы: `MAX_FILE_SIZE_FOR_CACHE = 1048576`, `MAX_LOCK_TRIES = 10`, `DEFAULT_DELIMITER = ','`
   - Свойства: `$csvDelimiter` (string), `$columns` (array), `$file` (SplFileObject)
   - Конструктор: `__construct($filename, $csvDelimiter = null)`

6. **CsvIntId** - CSV с автогенерацией ID
   - Наследует: `CsvBase`
   - Конструктор: `__construct($filename, $delimiter)`
   - Особенности: автоматическая генерация целочисленных ID

7. **SerializedDbTable** - сериализованные данные в БД
   - Наследует: `DbTable`
   - Свойства: `$tableName`
   - Конструктор: `__construct(TableGateway $dbTable, bool $writeLogs = false, ?LoggerInterface $loggerService = null)`
   - Методы: `__sleep()`, `__wakeup()`

8. **Cacheable** - кэшируемый DataStore
   - Реализует: `DataStoresInterface`, `RefreshableInterface`
   - Свойства: `$cashStore` (DataStoresInterface), `$dataSource` (DataSourceInterface)
   - Конструктор: `__construct(DataSourceInterface $dataSource, DataStoresInterface $cashStore = null)`

### Aspect DataStore классы (namespace: `rollun\datastore\DataStore\Aspect`):

9. **AspectAbstract** - базовый аспект
   - Реализует: `DataStoresInterface`, `DataStoreInterface`
   - Свойства: `$dataStore` (DataStoresInterface)
   - Конструктор: `__construct(DataStoresInterface $dataStore)`

10. **AspectWithEventManagerAbstract** - аспект с EventManager
    - Наследует: `AspectAbstract`
    - Реализует: `WithEventManagerInterface`
    - Свойства: `$eventManager` (EventManagerInterface), `$dataStoreName` (string)
    - Конструктор: `__construct(DataStoresInterface $dataStore, EventManagerInterface $eventManager = null, string $dataStoreName = null)`

11. **AspectEntityMapper** - маппинг сущностей
12. **AspectModifyTable** - модификация таблиц
13. **AspectReadOnly** - только для чтения
14. **AspectSchema** - работа со схемами
15. **AspectTyped** - типизированные данные

## Точные Factory классы

### DataStore Factory (namespace: `rollun\datastore\DataStore\Factory`):

1. **DataStoreAbstractFactory** - базовый абстрактный фабрика
   - Константы: `KEY_DATASTORE = 'dataStore'`, `KEY_WRITE_LOGS = 'writeLogs'`
   - Методы: `canCreate(ContainerInterface $container, $requestedName)`

2. **DbTableAbstractFactory** - фабрика для DbTable
   - Наследует: `DataStoreAbstractFactory`
   - Константы: `KEY_TABLE_NAME = 'tableName'`, `KEY_TABLE_GATEWAY = 'tableGateway'`, `KEY_DB_ADAPTER = 'dbAdapter'`
   - Методы: `__invoke(ContainerInterface $container, $requestedName, array $options = null)`

3. **MemoryAbstractFactory** - фабрика для Memory
   - Наследует: `DataStoreAbstractFactory`
   - Константы: `KEY_REQUIRED_COLUMNS = 'requiredColumns'`
   - Методы: `__invoke(ContainerInterface $container, $requestedName, array $options = null)`

4. **HttpClientAbstractFactory** - фабрика для HttpClient
5. **CsvAbstractFactory** - фабрика для CsvBase
6. **CacheableAbstractFactory** - фабрика для Cacheable

### Aspect Factory (namespace: `rollun\datastore\DataStore\Aspect\Factory`):

7. **AspectAbstractFactory** - фабрика для AspectAbstract
8. **AspectSchemaAbstractFactory** - фабрика для AspectSchema

## Точные RQL компоненты

### RQL Parser (namespace: `rollun\datastore\Rql`):

1. **RqlParser** - основной парсер RQL
   - Свойства: `$allowedAggregateFunction` (array), `$conditionBuilder` (RqlConditionBuilder)
   - Константы: `$nodes = ['select', 'sort', 'limit']`
   - Методы: `rqlDecode($rqlQueryString)`, `rqlEncode(Query $query)`, `decode($rqlQueryString)`, `encode(Query $query)`

2. **RqlQuery** - расширенный Query
   - Наследует: `Xiag\Rql\Parser\Query`
   - Свойства: `$groupBy` (GroupbyNode)
   - Методы: `setGroupBy(GroupbyNode $groupBy)`, `getGroupBy()`

3. **QueryParser** - парсер запросов

### RQL Nodes (namespace: `rollun\datastore\Rql\Node`):

4. **AggregateFunctionNode** - узел агрегатной функции
5. **AggregateSelectNode** - узел агрегатного SELECT
6. **AlikeGlobNode** - узел ALIKE GLOB
7. **AlikeNode** - узел ALIKE
8. **ContainsNode** - узел CONTAINS
9. **GroupbyNode** - узел GROUP BY
10. **LikeGlobNode** - узел LIKE GLOB

### RQL Binary Nodes (namespace: `rollun\datastore\Rql\Node\BinaryNode`):

11. **BinaryOperatorNodeAbstract** - абстрактный бинарный оператор
12. **EqfNode** - узел EQF (равенство с форматированием)
13. **EqnNode** - узел EQN (равенство с null)
14. **EqtNode** - узел EQT (равенство с типом)
15. **IeNode** - узел IE (is empty)

### RQL Token Parsers (namespace: `rollun\datastore\Rql\TokenParser`):

16. **GroupbyTokenParser** - парсер GROUP BY
17. **SelectTokenParser** - парсер SELECT

### RQL Query Token Parsers:

#### Basic Token Parsers (namespace: `rollun\datastore\Rql\TokenParser\Query\Basic`):

18. **BinaryOperator\BinaryTokenParserAbstract** - абстрактный бинарный парсер
19. **BinaryOperator\EqfTokenParser** - парсер EQF
20. **BinaryOperator\EqnTokenParser** - парсер EQN
21. **BinaryOperator\EqtTokenParser** - парсер EQT
22. **BinaryOperator\IeTokenParser** - парсер IE
23. **ScalarOperator\AlikeGlobTokenParser** - парсер ALIKE GLOB
24. **ScalarOperator\AlikeTokenParser** - парсер ALIKE
25. **ScalarOperator\ContainsTokenParser** - парсер CONTAINS
26. **ScalarOperator\LikeGlobTokenParser** - парсер LIKE GLOB
27. **ScalarOperator\MatchTokenParser** - парсер MATCH

#### Fiql Token Parsers (namespace: `rollun\datastore\Rql\TokenParser\Query\Fiql`):

28. **BinaryOperator\BinaryTokenParserAbstract** - абстрактный бинарный парсер
29. **BinaryOperator\EqfTokenParser** - парсер EQF
30. **BinaryOperator\EqnTokenParser** - парсер EQN
31. **BinaryOperator\EqtTokenParser** - парсер EQT
32. **BinaryOperator\IeTokenParser** - парсер IE
33. **ScalarOperator\AlikeGlobTokenParser** - парсер ALIKE GLOB
34. **ScalarOperator\AlikeTokenParser** - парсер ALIKE
35. **ScalarOperator\ContainsTokenParser** - парсер CONTAINS
36. **ScalarOperator\LikeGlobTokenParser** - парсер LIKE GLOB
37. **ScalarOperator\MatchTokenParser** - парсер MATCH

## Точные Middleware компоненты

### Основные Middleware (namespace: `rollun\datastore\Middleware`):

1. **DataStoreApi** - основной API middleware
   - Реализует: `MiddlewareInterface`
   - Свойства: `$middlewarePipe` (MiddlewarePipe), `$logger` (LoggerInterface)
   - Конструктор: `__construct(Determinator $determinator, RequestHandlerInterface $renderer = null, LoggerInterface $logger = null)`

2. **DataStoreRest** - REST обработчики
   - Реализует: `MiddlewareInterface`
   - Свойства: `$middlewarePipe` (MiddlewarePipe)
   - Конструктор: `__construct(DataStoresInterface $dataStore)`

3. **ResourceResolver** - извлечение ресурса из URL
   - Реализует: `MiddlewareInterface`
   - Константы: `BASE_PATH = '/api/datastore'`, `RESOURCE_NAME = 'resourceName'`, `PRIMARY_KEY_VALUE = 'primaryKeyValue'`
   - Свойства: `$basePath` (string)
   - Конструктор: `__construct($basePath = null)`

4. **RequestDecoder** - декодирование RQL и JSON
   - Реализует: `MiddlewareInterface`
   - Методы: `parseOverwriteMode()`, `parseRqlQuery()`, `parseHeaderLimit()`, `parseRequestBody()`, `parseContentRange()`

5. **Determinator** - выбор DataStore
   - Реализует: `MiddlewareInterface`
   - Свойства: `$dataStorePluginManager` (DataStorePluginManager)
   - Конструктор: `__construct(DataStorePluginManager $dataStorePluginManager)`

### Handler классы (namespace: `rollun\datastore\Middleware\Handler`):

6. **AbstractHandler** - базовый обработчик
   - Наследует: `DataStoreAbstract`
   - Абстрактные методы: `canHandle(ServerRequestInterface $request): bool`, `handle(ServerRequestInterface $request): ResponseInterface`

7. **QueryHandler** - GET с RQL запросом
   - Наследует: `AbstractHandler`
   - Методы: `canHandle()`, `handle()`, `createContentRange()`, `getTotalItems()`

8. **CreateHandler** - POST с одной записью
   - Наследует: `AbstractHandler`
   - Методы: `canHandle()`, `handle()`

9. **ReadHandler** - GET с primaryKeyValue
   - Наследует: `AbstractHandler`
   - Методы: `canHandle()`, `handle()`

10. **UpdateHandler** - PUT запросы
    - Наследует: `AbstractHandler`
    - Методы: `canHandle()`, `handle()`

11. **DeleteHandler** - DELETE с primaryKeyValue
    - Наследует: `AbstractHandler`
    - Методы: `canHandle()`, `handle()`

12. **MultiCreateHandler** - POST с массивом записей
    - Наследует: `AbstractHandler`
    - Методы: `canHandle()`, `handle()`

13. **HeadHandler** - HEAD запросы (метаданные)
    - Наследует: `AbstractHandler`
    - Методы: `canHandle()`, `handle()`

14. **RefreshHandler** - PATCH без RQL
    - Наследует: `AbstractHandler`
    - Методы: `canHandle()`, `handle()`

15. **QueriedUpdateHandler** - PATCH с RQL запросом
    - Наследует: `AbstractHandler`
    - Методы: `canHandle()`, `handle()`, `isRqlQueryNotContainsGroupByAndSelect()`

16. **DownloadCsvHandler** - GET с заголовком `download: csv`
    - Наследует: `AbstractHandler`
    - Константы: `HEADER = 'download'`, `DELIMITER = ','`, `ENCLOSURE = '"'`, `ESCAPE_CHAR = '\\'`, `LIMIT = 8000`
    - Методы: `canHandle()`, `handle()`, `process()`

17. **ErrorHandler** - обработка ошибок
    - Реализует: `MiddlewareInterface`
    - Методы: `process()`

### Factory классы (namespace: `rollun\datastore\Middleware\Factory`):

18. **DataStoreApiFactory** - фабрика для DataStoreApi
19. **DeterminatorFactory** - фабрика для Determinator

## Точные Repository компоненты

### Repository классы (namespace: `rollun\repository`):

1. **ModelRepository** - репозиторий моделей
   - Реализует: `ModelRepositoryInterface`
   - Свойства: `$dataStore` (DataStoreAbstract), `$modelClass` (string), `$mapper` (FieldMapperInterface), `$logger` (LoggerInterface)
   - Конструктор: `__construct(DataStoreAbstract $dataStore, string $modelClass, FieldMapperInterface $mapper = null, LoggerInterface $logger = null)`

2. **ModelAbstract** - базовая модель
   - Реализует: `ModelInterface`, `ModelHiddenFieldInterface`, `ArrayAccess`
   - Свойства: `$attributes` (array), `$original` (array), `$exists` (bool), `$casting` (array)
   - Конструктор: `__construct($attributes = [], $exists = false)`

### Repository Interfaces (namespace: `rollun\repository\Interfaces`):

3. **ModelRepositoryInterface** - интерфейс репозитория
4. **ModelInterface** - интерфейс модели
5. **ModelHiddenFieldInterface** - интерфейс скрытых полей
6. **ModelCastingInterface** - интерфейс приведения типов
7. **FieldMapperInterface** - интерфейс маппера полей

### Repository Traits (namespace: `rollun\repository\Traits`):

8. **ModelArrayAccess** - trait для ArrayAccess
9. **ModelDataTime** - trait для работы с датами
10. **ModelCastingTrait** - trait для приведения типов

### Repository Factory (namespace: `rollun\repository\Factory`):

11. **ModelRepositoryAbstractFactory** - фабрика для ModelRepository

## Точные Uploader компоненты

### Uploader классы (namespace: `rollun\uploader`):

1. **Uploader** - загрузчик данных
   - Свойства: `$sourceDataIteratorAggregator` (Traversable), `$destinationDataStore` (DataStoresInterface), `$key` (int)
   - Конструктор: `__construct(Traversable $sourceDataIteratorAggregator, DataStoresInterface $destinationDataStore)`
   - Методы: `upload()`, `__invoke()`, `__wakeup()`

### Uploader Iterator (namespace: `rollun\uploader\Iterator`):

2. **DataStorePack** - итератор пакетов
   - Реализует: `SeekableIterator`
   - Свойства: `$dataStore` (DataStoresInterface), `$current` (mixed), `$limit` (int)
   - Конструктор: `__construct(DataStoresInterface $dataStore, int $limit = 100)`
   - Методы: `current()`, `next()`, `key()`, `valid()`, `rewind()`, `seek($position)`, `getInitQuery()`, `getQuery()`

### Uploader Factory (namespace: `rollun\uploader\Factory`):

3. **UploaderAbstractFactory** - фабрика для Uploader

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

### CsvBase:
- `MAX_FILE_SIZE_FOR_CACHE = 1048576` - максимальный размер файла для кэша (1MB)
- `MAX_LOCK_TRIES = 10` - максимальное количество попыток блокировки
- `DEFAULT_DELIMITER = ','` - разделитель по умолчанию

### HttpClient:
- `DATASTORE_IDENTIFIER_HEADER = 'X_DATASTORE_IDENTIFIER'` - заголовок идентификатора

## Точные HTTP статус коды

- `200` - успешный запрос
- `201` - создан ресурс
- `204` - успешное удаление
- `404` - ресурс не найден
- `500` - внутренняя ошибка сервера

## Точные HTTP заголовки

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

## Точные RQL операторы

### Логические операторы:
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

### Строковые операторы:
- `like(field,pattern)` - LIKE с подстановочными символами
- `alike(field,pattern)` - LIKE без учета регистра
- `contains(field,value)` - содержит подстроку
- `match(field,regex)` - регулярное выражение

### Операторы выборки:
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

## Заключение

Данный анализ содержит максимально точную информацию, соответствующую реальному коду библиотеки. Устранены все расплывчатости, указаны точные namespace, классы, методы, константы и конфигурационные ключи. Документация готова для практического использования разработчиками.





