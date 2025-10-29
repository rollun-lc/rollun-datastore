# API Reference - rollun-datastore (Верифицированная версия)

## Содержание

- [HTTP API](#http-api)
- [DataStore Interfaces](#datastore-interfaces)
- [DataStore Implementations](#datastore-implementations)
- [RQL Components](#rql-components)
- [Middleware Components](#middleware-components)
- [Repository Components](#repository-components)
- [Uploader Components](#uploader-components)
- [Exceptions](#exceptions)

## HTTP API

### Базовый URL
```
/api/datastore
```

### Эндпоинты

#### GET /api/datastore/{resource}
- **Описание**: Получить список записей с RQL фильтрацией
- **Параметры**: RQL запрос в query string
- **Ответ**: JSON массив записей
- **Обработчик**: `rollun\datastore\Middleware\Handler\QueryHandler`
- **Пример**: `GET /api/datastore/users?eq(name,John)&sort(+id)&limit(10)`

#### GET /api/datastore/{resource}/{id}
- **Описание**: Получить запись по ID
- **Ответ**: JSON объект записи или 404
- **Обработчик**: `rollun\datastore\Middleware\Handler\ReadHandler`
- **Пример**: `GET /api/datastore/users/123`

#### POST /api/datastore/{resource}
- **Описание**: Создать новую запись
- **Тело**: JSON объект записи
- **Ответ**: JSON объект созданной записи (201)
- **Обработчик**: `rollun\datastore\Middleware\Handler\CreateHandler`
- **Пример**: `POST /api/datastore/users {"name": "John", "email": "john@example.com"}`

#### POST /api/datastore/{resource} (массовое создание)
- **Описание**: Создать несколько записей
- **Тело**: JSON массив записей
- **Ответ**: JSON массив ID созданных записей (201)
- **Обработчик**: `rollun\datastore\Middleware\Handler\MultiCreateHandler`
- **Пример**: `POST /api/datastore/users [{"name": "John"}, {"name": "Jane"}]`

#### PUT /api/datastore/{resource}/{id}
- **Описание**: Обновить запись по ID
- **Тело**: JSON объект с обновляемыми полями
- **Ответ**: JSON объект обновленной записи
- **Обработчик**: `rollun\datastore\Middleware\Handler\UpdateHandler`
- **Пример**: `PUT /api/datastore/users/123 {"name": "Jane"}`

#### DELETE /api/datastore/{resource}/{id}
- **Описание**: Удалить запись по ID
- **Ответ**: JSON объект удаленной записи или 204
- **Обработчик**: `rollun\datastore\Middleware\Handler\DeleteHandler`
- **Пример**: `DELETE /api/datastore/users/123`

#### PATCH /api/datastore/{resource}
- **Описание**: Обновить записи по RQL запросу
- **Тело**: JSON объект с обновляемыми полями
- **Параметры**: RQL запрос в query string
- **Ответ**: JSON массив ID обновленных записей
- **Обработчик**: `rollun\datastore\Middleware\Handler\QueriedUpdateHandler`
- **Пример**: `PATCH /api/datastore/users?eq(status,active) {"status": "inactive"}`

#### PATCH /api/datastore/{resource} (обновление)
- **Описание**: Обновить DataStore (если поддерживается)
- **Ответ**: JSON объект
- **Обработчик**: `rollun\datastore\Middleware\Handler\RefreshHandler`
- **Пример**: `PATCH /api/datastore/users`

#### HEAD /api/datastore/{resource}
- **Описание**: Получить метаданные ресурса
- **Ответ**: Заголовки с информацией о DataStore
- **Обработчик**: `rollun\datastore\Middleware\Handler\HeadHandler`
- **Пример**: `HEAD /api/datastore/users`

#### GET /api/datastore/{resource}?download=csv
- **Описание**: Экспорт данных в CSV
- **Ответ**: CSV файл
- **Обработчик**: `rollun\datastore\Middleware\Handler\DownloadCsvHandler`
- **Пример**: `GET /api/datastore/users?download=csv&eq(status,active)`

### HTTP Заголовки

#### Запросы
- `Content-Type: application/json` - JSON данные
- `If-Match: *` - режим перезаписи
- `With-Content-Range: *` - включить Content-Range
- `download: csv` - экспорт в CSV

#### Ответы
- `Content-Type: application/json` - JSON ответ
- `Content-Type: text/csv` - CSV ответ
- `Content-Range: items 1-10/100` - диапазон записей
- `X_DATASTORE_IDENTIFIER: id` - имя поля-идентификатора
- `X_MULTI_CREATE: true` - поддержка массового создания
- `X_QUERIED_UPDATE: true` - поддержка обновления по запросу
- `Datastore-Scheme: {...}` - схема DataStore
- `Location: /api/datastore/resource` - местоположение созданного ресурса

## DataStore Interfaces

### ReadInterface

```php
namespace rollun\datastore\DataStore\Interfaces;

interface ReadInterface extends \Countable, \IteratorAggregate
{
    public const DEF_ID = 'id';
    public const LIMIT_INFINITY = 2147483647;
    
    public function getIdentifier(): string;
    public function read($id): ?array;
    public function has($id): bool;
    public function query(Query $query): array;
}
```

### DataStoreInterface

```php
namespace rollun\datastore\DataStore\Interfaces;

interface DataStoreInterface extends ReadInterface
{
    public function create($record);
    public function multiCreate($records): array;
    public function update($record);
    public function multiUpdate($records): array;
    public function queriedUpdate($record, Query $query): array;
    public function rewrite($record);
    public function delete($id);
    public function queriedDelete(Query $query): array;
}
```

### DataStoresInterface

```php
namespace rollun\datastore\DataStore\Interfaces;

interface DataStoresInterface extends ReadInterface
{
    public function create($itemData, $rewriteIfExist = false);
    public function update($itemData, $createIfAbsent = false);
    public function delete($id);
    public function deleteAll();
}
```

## DataStore Implementations

### Memory DataStore

```php
namespace rollun\datastore\DataStore;

class Memory extends DataStoreAbstract
{
    protected $items = [];
    protected $columns = [];
    
    public function __construct(array $columns = []);
    public function read($id): ?array;
    public function create($itemData, $rewriteIfExist = false): array;
    public function update($itemData, $createIfAbsent = false): array;
    public function delete($id): ?array;
    public function deleteAll(): int;
    public function count(): int;
    public function getIterator(): \Traversable;
    protected function getKeys(): array;
    protected function checkOnExistingColumns($itemData);
}
```

### DbTable DataStore

```php
namespace rollun\datastore\DataStore;

class DbTable extends DataStoreAbstract
{
    public const LOG_METHOD = 'method';
    public const LOG_TABLE = 'table';
    public const LOG_TIME = 'time';
    public const LOG_REQUEST = 'request';
    public const LOG_RESPONSE = 'response';
    public const LOG_ROLLBACK = 'rollbackTransaction';
    public const LOG_SQL = 'sql';
    public const LOG_COUNT = 'count';
    
    public function __construct(
        TableGateway $dbTable,
        bool $writeLogs = false,
        ?LoggerInterface $loggerService = null
    );
    public function create($itemData, $rewriteIfExist = false): array;
    public function update($itemData, $createIfAbsent = false): array;
    public function delete($id): ?array;
    public function query(Query $query): array;
}
```

### HttpClient DataStore

```php
namespace rollun\datastore\DataStore;

class HttpClient extends DataStoreAbstract
{
    public const DATASTORE_IDENTIFIER_HEADER = 'X_DATASTORE_IDENTIFIER';
    
    public function __construct(
        Client $client,
        $url,
        $options = [],
        LifeCycleToken $lifeCycleToken = null
    );
    public function create($itemData, $rewriteIfExist = false): array;
    public function update($itemData, $createIfAbsent = false): array;
    public function delete($id): ?array;
    public function query(Query $query): array;
}
```

### CsvBase DataStore

```php
namespace rollun\datastore\DataStore;

class CsvBase extends DataStoreAbstract
{
    protected const MAX_FILE_SIZE_FOR_CACHE = 1048576; // 1MB
    protected const MAX_LOCK_TRIES = 10;
    protected const DEFAULT_DELIMITER = ',';
    
    public function __construct($filename, $csvDelimiter = null);
    public function create($itemData, $rewriteIfExist = false): array;
    public function update($itemData, $createIfAbsent = false): array;
    public function delete($id): ?array;
    public function query(Query $query): array;
}
```

## RQL Components

### RqlParser

```php
namespace rollun\datastore\Rql;

class RqlParser
{
    public function __construct(
        array $allowedAggregateFunction = null,
        ConditionBuilderAbstract $conditionBuilder = null
    );
    
    public static function rqlDecode($rqlQueryString): Query;
    public static function rqlEncode(Query $query): string;
    public function decode($rqlQueryString): Query;
    public function encode(Query $query): string;
}
```

### RqlQuery

```php
namespace rollun\datastore\Rql;

class RqlQuery extends Query
{
    public function __construct($query = null);
    public function setGroupBy(GroupbyNode $groupBy): RqlQuery;
    public function getGroupBy(): ?GroupbyNode;
}
```

### Поддерживаемые RQL операторы

#### Логические операторы
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

#### Строковые операторы
- `like(field,pattern)` - LIKE с подстановочными символами
- `alike(field,pattern)` - LIKE без учета регистра
- `contains(field,value)` - содержит подстроку
- `match(field,regex)` - регулярное выражение

#### Операторы выборки
- `sort(+field1,-field2)` - сортировка
- `limit(10,20)` - лимит и offset
- `select(field1,field2)` - выбор полей
- `groupby(field1,field2)` - группировка

#### Агрегатные функции
- `count(field)` - подсчет
- `max(field)` - максимум
- `min(field)` - минимум
- `sum(field)` - сумма
- `avg(field)` - среднее

## Middleware Components

### DataStoreApi

```php
namespace rollun\datastore\Middleware;

class DataStoreApi implements MiddlewareInterface
{
    public function __construct(
        Determinator $determinator,
        RequestHandlerInterface $renderer = null,
        LoggerInterface $logger = null
    );
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface;
}
```

### DataStoreRest

```php
namespace rollun\datastore\Middleware;

class DataStoreRest implements MiddlewareInterface
{
    public function __construct(DataStoresInterface $dataStore);
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface;
}
```

### ResourceResolver

```php
namespace rollun\datastore\Middleware;

class ResourceResolver implements MiddlewareInterface
{
    public const BASE_PATH = '/api/datastore';
    public const RESOURCE_NAME = 'resourceName';
    public const PRIMARY_KEY_VALUE = 'primaryKeyValue';
    
    public function __construct($basePath = null);
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface;
}
```

### RequestDecoder

```php
namespace rollun\datastore\Middleware;

class RequestDecoder implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface;
    protected function parseOverwriteMode(ServerRequestInterface $request): ServerRequestInterface;
    protected function parseRqlQuery(ServerRequestInterface $request): ServerRequestInterface;
    protected function parseHeaderLimit(ServerRequestInterface $request): ServerRequestInterface;
    protected function parseRequestBody(ServerRequestInterface $request): ServerRequestInterface;
    protected function parseContentRange(ServerRequestInterface $request): ServerRequestInterface;
}
```

### Determinator

```php
namespace rollun\datastore\Middleware;

class Determinator implements MiddlewareInterface
{
    public function __construct(DataStorePluginManager $dataStorePluginManager);
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface;
}
```

## Repository Components

### ModelRepository

```php
namespace rollun\repository;

class ModelRepository implements ModelRepositoryInterface
{
    public function __construct(
        DataStoreAbstract $dataStore,
        string $modelClass,
        FieldMapperInterface $mapper = null,
        LoggerInterface $logger = null
    );
    public function save(ModelInterface $model): ModelInterface;
    public function multiSave(array $models): array;
    public function find(Query $query): array;
    public function findById($id): ?ModelInterface;
    public function remove(ModelInterface $model): bool;
    public function removeById($id): bool;
    public function count(): int;
    public function getDataStore(): DataStoreAbstract;
    public function has($id): bool;
}
```

### ModelAbstract

```php
namespace rollun\repository;

class ModelAbstract implements ModelInterface, ModelHiddenFieldInterface, \ArrayAccess
{
    protected $attributes = [];
    protected $original = [];
    protected $exists = false;
    protected $casting = [];
    
    public function __construct($attributes = [], $exists = false);
    public function __set($key, $value);
    public function __get($key);
    public function __isset($key): bool;
    public function __unset($key);
    public function toArray(): array;
    public function getAttributes(): array;
    public function setAttribute($key, $value);
    public function getAttribute($key);
    public function hasAttribute($key): bool;
    public function isExists(): bool;
    public function isChanged(): bool;
}
```

## Uploader Components

### Uploader

```php
namespace rollun\uploader;

class Uploader
{
    public function __construct(
        \Traversable $sourceDataIteratorAggregator,
        DataStoresInterface $destinationDataStore
    );
    public function upload(): void;
    public function __invoke(): void;
}
```

### DataStorePack

```php
namespace rollun\uploader\Iterator;

class DataStorePack implements \SeekableIterator
{
    public function __construct(DataStoresInterface $dataStore, int $limit = 100);
    public function current();
    public function next(): void;
    public function key();
    public function valid(): bool;
    public function rewind(): void;
    public function seek($position): void;
}
```

## Exceptions

### DataStoreException

```php
namespace rollun\datastore\DataStore;

class DataStoreException extends \Exception
{
}
```

### RestException

```php
namespace rollun\datastore\Middleware;

class RestException extends \Exception
{
}
```

## Константы

### ReadInterface
- `DEF_ID = 'id'` - имя поля-идентификатора по умолчанию
- `LIMIT_INFINITY = 2147483647` - значение для неограниченного лимита

### ResourceResolver
- `BASE_PATH = '/api/datastore'` - базовый путь API
- `RESOURCE_NAME = 'resourceName'` - атрибут имени ресурса
- `PRIMARY_KEY_VALUE = 'primaryKeyValue'` - атрибут значения первичного ключа

### DbTable
- `LOG_METHOD = 'method'` - лог метода
- `LOG_TABLE = 'table'` - лог таблицы
- `LOG_TIME = 'time'` - лог времени
- `LOG_REQUEST = 'request'` - лог запроса
- `LOG_RESPONSE = 'response'` - лог ответа
- `LOG_ROLLBACK = 'rollbackTransaction'` - лог отката транзакции
- `LOG_SQL = 'sql'` - лог SQL
- `LOG_COUNT = 'count'` - лог количества

### DownloadCsvHandler
- `HEADER = 'download'` - заголовок для скачивания
- `DELIMITER = ','` - разделитель CSV
- `ENCLOSURE = '"'` - ограничитель CSV
- `ESCAPE_CHAR = '\\'` - экранирующий символ
- `LIMIT = 8000` - лимит записей для экспорта
