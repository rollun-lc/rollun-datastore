# Детальный анализ классов rollun-datastore

## Содержание

- [DataStore Interfaces](#datastore-interfaces)
- [DataStore Implementations](#datastore-implementations)
- [RQL Components](#rql-components)
- [Middleware Components](#middleware-components)
- [Repository Components](#repository-components)
- [Uploader Components](#uploader-components)
- [TableGateway Components](#tablegateway-components)
- [Aspect Components](#aspect-components)
- [Factory Components](#factory-components)

## DataStore Interfaces

### DataStoreInterface

**Namespace:** `rollun\datastore\DataStore\Interfaces\DataStoreInterface`

**Назначение:** Основной интерфейс для всех DataStore реализаций, определяющий CRUD операции.

**Методы:**
- `create($record)` - создание записи
- `multiCreate($records)` - массовое создание записей
- `update($record)` - обновление записи
- `multiUpdate($records)` - массовое обновление записей
- `delete($id)` - удаление записи по ID
- `multiDelete($ids)` - массовое удаление записей

**Наследует от:** `ReadInterface`

**Реализации:** `DataStoreAbstract`, `Memory`, `DbTable`, `HttpClient`, `CsvBase`, `CsvIntId`, `SerializedDbTable`, `Cacheable`

### DataStoresInterface

**Namespace:** `rollun\datastore\DataStore\Interfaces\DataStoresInterface`

**Назначение:** Расширенный интерфейс с дополнительными методами для работы с данными.

**Методы:**
- `create($itemData, $rewriteIfExist = false)` - создание с возможностью перезаписи
- `update($itemData, $createIfAbsent = false)` - обновление с возможностью вставки
- `queriedUpdate($itemData, Query $query)` - обновление по запросу
- `queriedDelete(Query $query)` - удаление по запросу
- `refresh($itemData, $revision)` - обновление с проверкой версии

**Наследует от:** `ReadInterface`

**Реализации:** `DataStoreAbstract`

### ReadInterface

**Namespace:** `rollun\datastore\DataStore\Interfaces\ReadInterface`

**Назначение:** Интерфейс для операций чтения данных.

**Константы:**
- `DEF_ID = 'id'` - имя поля-идентификатора по умолчанию
- `LIMIT_INFINITY = 2147483647` - значение для неограниченного лимита

**Методы:**
- `getIdentifier()` - получение имени поля-идентификатора
- `read($id)` - чтение записи по ID
- `has($id)` - проверка существования записи
- `query(Query $query)` - выполнение запроса
- `count()` - получение количества записей
- `getIterator()` - получение итератора

**Наследует от:** `\Countable`, `\IteratorAggregate`

**Реализации:** `DataStoreAbstract`, все DataStore реализации

## DataStore Implementations

### DataStoreAbstract

**Namespace:** `rollun\datastore\DataStore\DataStoreAbstract`

**Назначение:** Абстрактный базовый класс для всех DataStore реализаций.

**Свойства:**
- `protected $conditionBuilder` - построитель условий для RQL

**Методы:**
- `__construct()` - конструктор
- `getIdentifier()` - получение идентификатора
- `read($id)` - чтение записи
- `has($id)` - проверка существования
- `query(Query $query)` - выполнение запроса
- `create($record)` - создание записи
- `multiCreate($records)` - массовое создание
- `update($record)` - обновление записи
- `multiUpdate($records)` - массовое обновление
- `delete($id)` - удаление записи
- `multiDelete($ids)` - массовое удаление
- `count()` - подсчет записей
- `getIterator()` - получение итератора

**Наследует от:** `DataStoresInterface`, `DataStoreInterface`

**Наследники:** `Memory`, `DbTable`, `HttpClient`, `CsvBase`, `CsvIntId`, `SerializedDbTable`, `Cacheable`

### Memory

**Namespace:** `rollun\datastore\DataStore\Memory`

**Назначение:** DataStore для хранения данных в оперативной памяти.

**Свойства:**
- `protected $items = []` - массив для хранения данных
- `protected $columns` - обязательные поля

**Конструктор:**
```php
public function __construct(array $columns = [])
```

**Особенности:**
- Использует `PhpConditionBuilder` для фильтрации
- Ключ массива = идентификатор записи
- Поддерживает валидацию обязательных полей
- Данные теряются при завершении скрипта

**Методы:**
- `read($id)` - чтение из массива
- `create($itemData, $rewriteIfExist = false)` - добавление в массив
- `update($itemData, $createIfAbsent = false)` - обновление в массиве
- `delete($id)` - удаление из массива
- `query(Query $query)` - фильтрация массива

### DbTable

**Namespace:** `rollun\datastore\DataStore\DbTable`

**Назначение:** DataStore для работы с таблицами базы данных.

**Константы логирования:**
- `LOG_METHOD = 'method'`
- `LOG_TABLE = 'table'`
- `LOG_TIME = 'time'`
- `LOG_REQUEST = 'request'`
- `LOG_RESPONSE = 'response'`
- `LOG_ROLLBACK = 'rollbackTransaction'`
- `LOG_SQL = 'sql'`
- `LOG_COUNT = 'count'`

**Свойства:**
- `protected $dbTable` - TableGateway для работы с БД
- `protected $sqlQueryBuilder` - построитель SQL запросов
- `protected $writeLogs` - флаг логирования
- `protected $loggerService` - сервис логирования

**Конструктор:**
```php
public function __construct(
    TableGateway $dbTable,
    bool $writeLogs = false,
    ?LoggerInterface $loggerService = null
)
```

**Особенности:**
- Использует Laminas TableGateway
- Поддерживает логирование SQL запросов
- Использует `SqlConditionBuilder` для RQL → SQL
- Поддерживает транзакции

**Методы:**
- `getSqlQueryBuilder()` - получение построителя SQL
- `create($itemData, $rewriteIfExist = false)` - создание записи в БД
- `update($itemData, $createIfAbsent = false)` - обновление записи в БД
- `delete($id)` - удаление записи из БД
- `query(Query $query)` - выполнение SQL запроса

### HttpClient

**Namespace:** `rollun\datastore\DataStore\HttpClient`

**Назначение:** DataStore для работы с внешними HTTP API.

**Константы:**
- `DATASTORE_IDENTIFIER_HEADER = 'X_DATASTORE_IDENTIFIER'`

**Свойства:**
- `protected $url` - базовый URL API
- `protected $login` - логин для Basic Auth
- `protected $password` - пароль для Basic Auth
- `protected $client` - HTTP клиент
- `protected $options` - опции HTTP клиента
- `protected $lifeCycleToken` - токен жизненного цикла
- `protected $identifier` - идентификатор

**Конструктор:**
```php
public function __construct(
    Client $client,
    $url,
    $options = [],
    LifeCycleToken $lifeCycleToken = null
)
```

**Особенности:**
- Использует Laminas HTTP Client
- Поддерживает Basic Authentication
- RQL запросы передаются в URL параметрах
- Поддерживает различные HTTP опции

**Методы:**
- `create($itemData, $rewriteIfExist = false)` - POST запрос
- `update($itemData, $createIfAbsent = false)` - PUT запрос
- `delete($id)` - DELETE запрос
- `query(Query $query)` - GET запрос с RQL

### CsvBase

**Namespace:** `rollun\datastore\DataStore\CsvBase`

**Назначение:** DataStore для работы с CSV файлами.

**Константы:**
- `MAX_FILE_SIZE_FOR_CACHE = 8388608` - максимальный размер файла для кэширования
- `MAX_LOCK_TRIES = 30` - максимальное количество попыток блокировки
- `DEFAULT_DELIMITER = ';'` - разделитель по умолчанию

**Свойства:**
- `protected $filename` - путь к CSV файлу
- `protected $csvDelimiter` - разделитель полей
- `protected $columns` - заголовки колонок
- `protected $file` - файловый объект

**Конструктор:**
```php
public function __construct(
    protected string $filename,
    ?string $csvDelimiter
)
```

**Особенности:**
- Использует `SplFileObject` для работы с файлами
- Поддерживает блокировку файлов
- Автоматически определяет заголовки из первой строки
- Использует `PhpConditionBuilder` для фильтрации

**Методы:**
- `getHeaders()` - получение заголовков из файла
- `enableReadMode()` - включение режима чтения
- `enableWriteMode()` - включение режима записи
- `create($itemData, $rewriteIfExist = false)` - добавление записи в CSV
- `update($itemData, $createIfAbsent = false)` - обновление записи в CSV
- `delete($id)` - удаление записи из CSV
- `query(Query $query)` - фильтрация CSV данных

### CsvIntId

**Namespace:** `rollun\datastore\DataStore\CsvIntId`

**Назначение:** Расширение CsvBase с автоматической генерацией целочисленных ID.

**Свойства:**
- `protected $nextId = 1` - следующий доступный ID

**Методы:**
- `getNextId()` - получение следующего ID
- `create($itemData, $rewriteIfExist = false)` - создание с автогенерацией ID

**Наследует от:** `CsvBase`

### SerializedDbTable

**Namespace:** `rollun\datastore\DataStore\SerializedDbTable`

**Назначение:** DataStore для сериализованных данных в БД.

**Методы:**
- `serializeItemData($itemData)` - сериализация данных перед сохранением
- `unserializeItemData($itemData)` - десериализация данных после чтения

**Наследует от:** `DbTable`

### Cacheable

**Namespace:** `rollun\datastore\DataStore\Cacheable`

**Назначение:** DataStore с кэшированием результатов.

**Свойства:**
- `protected $dataSource` - источник данных
- `protected $cacheable` - кэшируемый DataStore

**Конструктор:**
```php
public function __construct(
    DataSourceInterface $dataSource,
    DataStoreInterface $cacheable
)
```

**Методы:**
- `query(Query $query)` - запрос с проверкой кэша
- `create($record)` - создание с инвалидацией кэша
- `update($record)` - обновление с инвалидацией кэша
- `delete($id)` - удаление с инвалидацией кэша

## RQL Components

### RqlParser

**Namespace:** `rollun\datastore\Rql\RqlParser`

**Назначение:** Парсер RQL строк в объекты Query.

**Свойства:**
- `private $allowedAggregateFunction` - разрешенные агрегатные функции
- `private $conditionBuilder` - построитель условий

**Конструктор:**
```php
public function __construct(
    array $allowedAggregateFunction = null,
    ConditionBuilderAbstract $conditionBuilder = null
)
```

**Статические методы:**
- `rqlDecode($rqlQueryString)` - декодирование RQL строки
- `rqlEncode($query)` - кодирование Query в RQL строку

**Методы:**
- `decode($rqlQueryString)` - декодирование (экземпляр)
- `encode(Query $query)` - кодирование (экземпляр)
- `prepareStringRql($rqlQueryString)` - подготовка RQL строки
- `encodedStrQuery($rqlQueryString)` - кодирование строки запроса

### RqlQuery

**Namespace:** `rollun\datastore\Rql\RqlQuery`

**Назначение:** Расширенный Query с поддержкой GROUP BY.

**Свойства:**
- `protected $groupBy` - узел группировки

**Конструктор:**
```php
public function __construct($query = null)
```

**Методы:**
- `setGroupBy(GroupbyNode $groupBy)` - установка группировки
- `getGroupBy()` - получение группировки

**Наследует от:** `Query`

### RqlQueryBuilder

**Namespace:** `rollun\datastore\Rql\RqlQueryBuilder`

**Назначение:** Построитель RQL запросов.

**Методы:**
- `buildQuery($conditions)` - построение запроса из условий
- `buildSort($sortFields)` - построение сортировки
- `buildLimit($limit, $offset)` - построение лимита

## Middleware Components

### DataStoreApi

**Namespace:** `rollun\datastore\Middleware\DataStoreApi`

**Назначение:** Основной middleware для HTTP API.

**Свойства:**
- `protected $middlewarePipe` - pipeline middleware
- `protected $logger` - сервис логирования

**Конструктор:**
```php
public function __construct(
    Determinator $determinator,
    RequestHandlerInterface $renderer = null,
    LoggerInterface $logger = null
)
```

**Методы:**
- `process(ServerRequestInterface $request, RequestHandlerInterface $handler)` - обработка запроса

### DataStoreRest

**Namespace:** `rollun\datastore\Middleware\DataStoreRest`

**Назначение:** REST обработчики для различных HTTP методов.

**Свойства:**
- `protected $middlewarePipe` - pipeline middleware
- `private $dataStore` - DataStore

**Конструктор:**
```php
public function __construct(private DataStoresInterface $dataStore)
```

**Методы:**
- `process(ServerRequestInterface $request, RequestHandlerInterface $handler)` - обработка запроса

### RequestDecoder

**Namespace:** `rollun\datastore\Middleware\RequestDecoder`

**Назначение:** Middleware для декодирования HTTP запросов.

**Методы:**
- `process(ServerRequestInterface $request, RequestHandlerInterface $handler)` - обработка запроса
- `parseOverwriteMode(ServerRequestInterface $request)` - парсинг режима перезаписи
- `parseRqlQuery(ServerRequestInterface $request)` - парсинг RQL запроса
- `parseHeaderLimit(ServerRequestInterface $request)` - парсинг лимита из заголовков
- `parseRequestBody(ServerRequestInterface $request)` - парсинг тела запроса
- `parseContentRange(ServerRequestInterface $request)` - парсинг Content-Range

### ResourceResolver

**Namespace:** `rollun\datastore\Middleware\ResourceResolver`

**Назначение:** Middleware для определения ресурса из URL.

**Методы:**
- `process(ServerRequestInterface $request, RequestHandlerInterface $handler)` - обработка запроса

### Determinator

**Namespace:** `rollun\datastore\Middleware\Determinator`

**Назначение:** Middleware для выбора подходящего DataStore.

**Свойства:**
- `protected $dataStorePluginManager` - менеджер DataStore

**Конструктор:**
```php
public function __construct(DataStorePluginManager $dataStorePluginManager)
```

**Методы:**
- `process(ServerRequestInterface $request, RequestHandlerInterface $handler)` - обработка запроса

### Handler\AbstractHandler

**Namespace:** `rollun\datastore\Middleware\Handler\AbstractHandler`

**Назначение:** Абстрактный базовый класс для обработчиков HTTP методов.

**Методы:**
- `canHandle(ServerRequestInterface $request)` - проверка возможности обработки
- `handle(ServerRequestInterface $request)` - обработка запроса
- `process(ServerRequestInterface $request, RequestHandlerInterface $handler)` - обработка через pipeline
- `isRqlQueryEmpty($request)` - проверка пустоты RQL запроса

**Наследует от:** `DataStoreAbstract`

## Repository Components

### ModelRepository

**Namespace:** `rollun\repository\ModelRepository`

**Назначение:** Основной репозиторий для работы с моделями.

**Свойства:**
- `protected $dataStore` - DataStore
- `protected $modelClass` - класс модели
- `protected $mapper` - маппер полей
- `protected $logger` - сервис логирования

**Конструктор:**
```php
public function __construct(
    DataStoreAbstract $dataStore,
    string $modelClass,
    FieldMapperInterface $mapper = null,
    LoggerInterface $logger
)
```

**Методы:**
- `save(ModelInterface $model)` - сохранение модели
- `multiSave(array $models)` - массовое сохранение
- `find(Query $query)` - поиск моделей
- `findById($id)` - поиск по ID
- `remove(ModelInterface $model)` - удаление модели
- `removeById($id)` - удаление по ID
- `count()` - подсчет моделей
- `getDataStore()` - получение DataStore
- `has($id)` - проверка существования
- `make($record)` - создание модели
- `makeModels($records)` - создание массива моделей
- `insertModel(ModelInterface $model)` - вставка модели
- `updateModel(ModelInterface $model)` - обновление модели
- `multiInserModels($models)` - массовая вставка
- `multiUpdateModels($models)` - массовое обновление

### ModelAbstract

**Namespace:** `rollun\repository\ModelAbstract`

**Назначение:** Абстрактная модель с базовой функциональностью.

**Свойства:**
- `protected $attributes = []` - атрибуты модели
- `protected $original = []` - оригинальные атрибуты
- `protected $exists = false` - флаг существования
- `protected $casting = []` - правила приведения типов

**Конструктор:**
```php
public function __construct($attributes = [], $exists = false)
```

**Методы:**
- `fill($attributes)` - заполнение атрибутов
- `getAttribute($name)` - получение атрибута
- `setAttribute($name, $value)` - установка атрибута
- `hasAttribute($name)` - проверка наличия атрибута
- `toArray()` - преобразование в массив
- `setExists($exists)` - установка флага существования
- `isExists()` - проверка существования
- `isChanged()` - проверка изменений
- `updateOriginal()` - обновление оригинальных данных
- `__set($name, $value)` - магический метод установки
- `__get($name)` - магический метод получения
- `__isset($name)` - магический метод проверки
- `__unset($name)` - магический метод удаления

**Наследует от:** `ModelInterface`, `ModelHiddenFieldInterface`, `ArrayAccess`

### ModelInterface

**Namespace:** `rollun\repository\Interfaces\ModelInterface`

**Назначение:** Интерфейс для моделей данных.

**Методы:**
- `toArray()` - преобразование в массив
- `setExists($exists)` - установка флага существования
- `isExists()` - проверка существования
- `isChanged()` - проверка изменений

### FieldMapperInterface

**Namespace:** `rollun\repository\Interfaces\FieldMapperInterface`

**Назначение:** Интерфейс для мапперов полей.

**Методы:**
- `map(array $data)` - маппинг данных

## Uploader Components

### Uploader

**Namespace:** `rollun\uploader\Uploader`

**Назначение:** Основной класс для загрузки данных.

**Свойства:**
- `protected $sourceDataIteratorAggregator` - итератор источника данных
- `protected $destinationDataStore` - целевой DataStore
- `protected $key` - позиция итератора

**Конструктор:**
```php
public function __construct(
    Traversable $sourceDataIteratorAggregator,
    DataStoresInterface $destinationDataStore
)
```

**Методы:**
- `upload()` - выполнение загрузки
- `__invoke($v = null)` - вызов загрузки
- `__wakeup()` - восстановление после сериализации

### DataStorePack

**Namespace:** `rollun\uploader\Iterator\DataStorePack`

**Назначение:** Итератор для пакетной обработки данных из DataStore.

**Свойства:**
- `protected $dataStore` - DataStore
- `protected $current` - текущая запись
- `protected $limit` - размер пакета

**Конструктор:**
```php
public function __construct(DataStoresInterface $dataStore, protected $limit = 100)
```

**Методы:**
- `current()` - получение текущей записи
- `next()` - переход к следующей записи
- `key()` - получение ключа текущей записи
- `valid()` - проверка валидности позиции
- `rewind()` - сброс итератора
- `seek($position)` - переход к позиции
- `getInitQuery()` - получение начального запроса
- `getQuery()` - получение запроса с лимитом

**Реализует:** `SeekableIterator`

## TableGateway Components

### SqlQueryBuilder

**Namespace:** `rollun\datastore\TableGateway\SqlQueryBuilder`

**Назначение:** Построитель SQL запросов.

**Свойства:**
- `protected $adapter` - адаптер БД
- `protected $table` - имя таблицы

**Конструктор:**
```php
public function __construct(AdapterInterface $adapter, $table)
```

**Методы:**
- `buildSelect(Query $query)` - построение SELECT запроса
- `buildInsert($data)` - построение INSERT запроса
- `buildUpdate($data, $where)` - построение UPDATE запроса
- `buildDelete($where)` - построение DELETE запроса

### TableManagerMysql

**Namespace:** `rollun\datastore\TableGateway\TableManagerMysql`

**Назначение:** Менеджер для управления таблицами MySQL.

**Свойства:**
- `protected $adapter` - адаптер БД
- `protected $tablesConfigs` - конфигурации таблиц
- `protected $autocreateTables` - автсоздание таблиц

**Методы:**
- `createTable($tableName, $config)` - создание таблицы
- `dropTable($tableName)` - удаление таблицы
- `tableExists($tableName)` - проверка существования таблицы
- `getTableColumns($tableName)` - получение колонок таблицы

## Aspect Components

### AspectAbstract

**Namespace:** `rollun\datastore\DataStore\Aspect\AspectAbstract`

**Назначение:** Абстрактный базовый класс для аспектов.

**Свойства:**
- `protected $dataStore` - обернутый DataStore

**Конструктор:**
```php
public function __construct(DataStoreInterface $dataStore)
```

**Методы:**
- Все методы DataStore с делегированием к обернутому DataStore

**Наследует от:** `DataStoreAbstract`

### AspectWithEventManagerAbstract

**Namespace:** `rollun\datastore\DataStore\Aspect\AspectWithEventManagerAbstract`

**Назначение:** Аспект с поддержкой событий.

**Свойства:**
- `protected $eventManager` - менеджер событий

**Методы:**
- `triggerEvent($eventName, $params)` - генерация события
- `attachListener($eventName, $callback)` - добавление слушателя

**Наследует от:** `AspectAbstract`

## Factory Components

### DataStoreAbstractFactory

**Namespace:** `rollun\datastore\DataStore\Factory\DataStoreAbstractFactory`

**Назначение:** Abstract Factory для создания DataStore.

**Константы:**
- `KEY_DATASTORE = 'dataStore'`
- `KEY_CLASS = 'class'`

**Методы:**
- `canCreate(ContainerInterface $container, $requestedName)` - проверка возможности создания
- `__invoke(ContainerInterface $container, $requestedName, array $options = null)` - создание объекта

**Реализует:** `AbstractFactoryInterface`

### DbTableAbstractFactory

**Namespace:** `rollun\datastore\DataStore\Factory\DbTableAbstractFactory`

**Назначение:** Abstract Factory для создания DbTable.

**Методы:**
- `canCreate(ContainerInterface $container, $requestedName)` - проверка возможности создания
- `__invoke(ContainerInterface $container, $requestedName, array $options = null)` - создание DbTable

### MemoryAbstractFactory

**Namespace:** `rollun\datastore\DataStore\Factory\MemoryAbstractFactory`

**Назначение:** Abstract Factory для создания Memory DataStore.

**Методы:**
- `canCreate(ContainerInterface $container, $requestedName)` - проверка возможности создания
- `__invoke(ContainerInterface $container, $requestedName, array $options = null)` - создание Memory

### HttpClientAbstractFactory

**Namespace:** `rollun\datastore\DataStore\Factory\HttpClientAbstractFactory`

**Назначение:** Abstract Factory для создания HttpClient.

**Методы:**
- `canCreate(ContainerInterface $container, $requestedName)` - проверка возможности создания
- `__invoke(ContainerInterface $container, $requestedName, array $options = null)` - создание HttpClient

### CsvAbstractFactory

**Namespace:** `rollun\datastore\DataStore\Factory\CsvAbstractFactory`

**Назначение:** Abstract Factory для создания CSV DataStore.

**Методы:**
- `canCreate(ContainerInterface $container, $requestedName)` - проверка возможности создания
- `__invoke(ContainerInterface $container, $requestedName, array $options = null)` - создание CSV DataStore

### CacheableAbstractFactory

**Namespace:** `rollun\datastore\DataStore\Factory\CacheableAbstractFactory`

**Назначение:** Abstract Factory для создания Cacheable DataStore.

**Методы:**
- `canCreate(ContainerInterface $container, $requestedName)` - проверка возможности создания
- `__invoke(ContainerInterface $container, $requestedName, array $options = null)` - создание Cacheable

### ModelRepositoryAbstractFactory

**Namespace:** `rollun\repository\Factory\ModelRepositoryAbstractFactory`

**Назначение:** Abstract Factory для создания ModelRepository.

**Константы:**
- `KEY_MODEL_REPOSITORY = 'modelRepository'`
- `KEY_CLASS = 'class'`
- `KEY_DATASTORE = 'dataStore'`
- `KEY_MODEL = 'model'`
- `KEY_MAPPER = 'mapper'`

**Методы:**
- `canCreate(ContainerInterface $container, $requestedName)` - проверка возможности создания
- `__invoke(ContainerInterface $container, $requestedName, array $options = null)` - создание ModelRepository

### UploaderAbstractFactory

**Namespace:** `rollun\uploader\Factory\UploaderAbstractFactory`

**Назначение:** Abstract Factory для создания Uploader.

**Константы:**
- `KEY = UploaderAbstractFactory::class`
- `KEY_SOURCE_DATA_ITERATOR_AGGREGATOR = "SourceDataIteratorAggregator"`
- `KEY_DESTINATION_DATA_STORE = "DestinationDataStore"`

**Методы:**
- `canCreate(ContainerInterface $container, $requestedName)` - проверка возможности создания
- `__invoke(ContainerInterface $container, $requestedName, array $options = null)` - создание Uploader
