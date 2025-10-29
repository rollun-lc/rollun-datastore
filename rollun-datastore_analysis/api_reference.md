# API Reference - rollun-datastore

## Содержание

- [DataStore Interfaces](#datastore-interfaces)
- [DataStore Implementations](#datastore-implementations)
- [RQL Components](#rql-components)
- [Middleware Components](#middleware-components)
- [Repository Components](#repository-components)
- [Uploader Components](#uploader-components)
- [Exceptions](#exceptions)

## DataStore Interfaces

### DataStoreInterface

Основной интерфейс для всех DataStore реализаций.

```php
interface DataStoreInterface extends ReadInterface
{
    /**
     * Создать новую запись
     * 
     * @param array|\ArrayObject|BaseDto|object $record
     * @return array|\ArrayObject|BaseDto|object
     * @throws DataStoreException
     */
    public function create($record);

    /**
     * Создать несколько записей
     * 
     * @param array[]|\ArrayObject[]|object $records
     * @return array Массив созданных идентификаторов
     * @throws DataStoreException
     */
    public function multiCreate($records);

    /**
     * Обновить существующую запись
     * 
     * @param array|\ArrayObject|BaseDto|object $record
     * @return array|\ArrayObject|BaseDto|object
     * @throws DataStoreException
     */
    public function update($record);

    /**
     * Обновить несколько записей
     * 
     * @param array[]|\ArrayObject[]|object $records
     * @return array Массив обновленных идентификаторов
     * @throws DataStoreException
     */
    public function multiUpdate($records);

    /**
     * Удалить запись по идентификатору
     * 
     * @param int|string $id
     * @return bool
     * @throws DataStoreException
     */
    public function delete($id);

    /**
     * Удалить несколько записей
     * 
     * @param array $ids
     * @return array Массив удаленных идентификаторов
     * @throws DataStoreException
     */
    public function multiDelete($ids);
}
```

### DataStoresInterface

Расширенный интерфейс с дополнительными методами.

```php
interface DataStoresInterface extends ReadInterface
{
    /**
     * Создать запись с возможностью перезаписи
     * 
     * @param array $itemData
     * @param bool $rewriteIfExist
     * @return array
     * @throws DataStoreException
     */
    public function create($itemData, $rewriteIfExist = false);

    /**
     * Обновить запись с возможностью вставки
     * 
     * @param array $itemData
     * @param bool $createIfAbsent
     * @return array
     * @throws DataStoreException
     */
    public function update($itemData, $createIfAbsent = false);

    /**
     * Обновить записи по запросу
     * 
     * @param array $itemData
     * @param Query $query
     * @return int Количество обновленных записей
     * @throws DataStoreException
     */
    public function queriedUpdate($itemData, Query $query);

    /**
     * Удалить записи по запросу
     * 
     * @param Query $query
     * @return int Количество удаленных записей
     * @throws DataStoreException
     */
    public function queriedDelete(Query $query);

    /**
     * Обновить запись с проверкой версии
     * 
     * @param array $itemData
     * @param int $revision
     * @return array
     * @throws DataStoreException
     */
    public function refresh($itemData, $revision);
}
```

### ReadInterface

Интерфейс для чтения данных.

```php
interface ReadInterface extends \Countable, \IteratorAggregate
{
    public const DEF_ID = 'id';
    public const LIMIT_INFINITY = 2147483647;

    /**
     * Получить имя поля-идентификатора
     * 
     * @return string
     */
    public function getIdentifier();

    /**
     * Прочитать запись по идентификатору
     * 
     * @param int|string $id
     * @return array|null
     */
    public function read($id);

    /**
     * Проверить существование записи
     * 
     * @param int|string $id
     * @return bool
     */
    public function has($id);

    /**
     * Выполнить запрос
     * 
     * @param Query $query
     * @return array
     * @throws DataStoreException
     */
    public function query(Query $query);

    /**
     * Получить количество записей
     * 
     * @return int
     */
    public function count();

    /**
     * Получить итератор
     * 
     * @return \Iterator
     */
    public function getIterator();
}
```

## DataStore Implementations

### Memory

DataStore в оперативной памяти.

```php
class Memory extends DataStoreAbstract
{
    /**
     * @param array $columns Обязательные поля
     */
    public function __construct(array $columns = []);

    /**
     * @var array $items Хранимые данные
     */
    protected $items = [];

    /**
     * @var array $columns Обязательные поля
     */
    protected $columns;
}
```

**Особенности:**
- Данные хранятся в массиве `$items`
- Ключ массива = идентификатор записи
- Поддерживает валидацию обязательных полей
- Использует `PhpConditionBuilder` для фильтрации

### DbTable

DataStore для работы с таблицами базы данных.

```php
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

    /**
     * @param TableGateway $dbTable
     * @param bool $writeLogs
     * @param LoggerInterface|null $loggerService
     */
    public function __construct(
        TableGateway $dbTable,
        bool $writeLogs = false,
        ?LoggerInterface $loggerService = null
    );

    /**
     * @var TableGateway $dbTable
     */
    protected $dbTable;

    /**
     * @var SqlQueryBuilder $sqlQueryBuilder
     */
    protected $sqlQueryBuilder;

    /**
     * @var bool $writeLogs
     */
    protected $writeLogs;

    /**
     * @var LoggerInterface $loggerService
     */
    protected $loggerService;
}
```

**Особенности:**
- Использует Laminas TableGateway
- Поддерживает логирование SQL запросов
- Использует `SqlConditionBuilder` для RQL → SQL
- Поддерживает транзакции

### HttpClient

DataStore для работы с внешними HTTP API.

```php
class HttpClient extends DataStoreAbstract
{
    protected const DATASTORE_IDENTIFIER_HEADER = 'X_DATASTORE_IDENTIFIER';

    /**
     * @param Client $client
     * @param string $url
     * @param array $options
     * @param LifeCycleToken|null $lifeCycleToken
     */
    public function __construct(
        Client $client, 
        $url, 
        $options = [], 
        LifeCycleToken $lifeCycleToken = null
    );

    /**
     * @var string $url Базовый URL API
     */
    protected $url;

    /**
     * @var string $login Логин для Basic Auth
     */
    protected $login;

    /**
     * @var string $password Пароль для Basic Auth
     */
    protected $password;

    /**
     * @var Client $client HTTP клиент
     */
    protected $client;

    /**
     * @var array $options Опции HTTP клиента
     */
    protected $options;

    /**
     * @var LifeCycleToken $lifeCycleToken Токен жизненного цикла
     */
    protected $lifeCycleToken;
}
```

**Особенности:**
- Использует Laminas HTTP Client
- Поддерживает Basic Authentication
- RQL запросы передаются в URL параметрах
- Поддерживает различные HTTP опции

### CsvBase

DataStore для работы с CSV файлами.

```php
class CsvBase extends DataStoreAbstract implements DataSourceInterface
{
    protected const MAX_FILE_SIZE_FOR_CACHE = 8388608;
    protected const MAX_LOCK_TRIES = 30;
    protected const DEFAULT_DELIMITER = ';';

    /**
     * @param string $filename Путь к CSV файлу
     * @param string|null $csvDelimiter Разделитель полей
     * @throws DataStoreException
     */
    public function __construct(
        protected string $filename,
        ?string $csvDelimiter
    );

    /**
     * @var string $csvDelimiter Разделитель полей
     */
    protected string $csvDelimiter;

    /**
     * @var array $columns Заголовки колонок
     */
    protected array $columns;

    /**
     * @var SplFileObject|null $file Файловый объект
     */
    protected ?SplFileObject $file = null;
}
```

**Особенности:**
- Использует `SplFileObject` для работы с файлами
- Поддерживает блокировку файлов
- Автоматически определяет заголовки из первой строки
- Использует `PhpConditionBuilder` для фильтрации

### CsvIntId

Расширение CsvBase с автоматической генерацией целочисленных ID.

```php
class CsvIntId extends CsvBase
{
    /**
     * @var int $nextId Следующий доступный ID
     */
    protected $nextId = 1;

    /**
     * Получить следующий ID
     * 
     * @return int
     */
    protected function getNextId();
}
```

### SerializedDbTable

DataStore для сериализованных данных в БД.

```php
class SerializedDbTable extends DbTable
{
    /**
     * Сериализация данных перед сохранением
     * 
     * @param array $itemData
     * @return array
     */
    protected function serializeItemData($itemData);

    /**
     * Десериализация данных после чтения
     * 
     * @param array $itemData
     * @return array
     */
    protected function unserializeItemData($itemData);
}
```

### Cacheable

DataStore с кэшированием.

```php
class Cacheable extends DataStoreAbstract
{
    /**
     * @param DataSourceInterface $dataSource Источник данных
     * @param DataStoreInterface $cacheable Кэшируемый DataStore
     */
    public function __construct(
        DataSourceInterface $dataSource,
        DataStoreInterface $cacheable
    );

    /**
     * @var DataSourceInterface $dataSource
     */
    protected $dataSource;

    /**
     * @var DataStoreInterface $cacheable
     */
    protected $cacheable;
}
```

## RQL Components

### RqlParser

Парсер RQL строк.

```php
class RqlParser
{
    /**
     * @param array|null $allowedAggregateFunction
     * @param ConditionBuilderAbstract|null $conditionBuilder
     */
    public function __construct(
        array $allowedAggregateFunction = null,
        ConditionBuilderAbstract $conditionBuilder = null
    );

    /**
     * Декодировать RQL строку в Query объект
     * 
     * @param string $rqlQueryString
     * @return Query
     */
    public static function rqlDecode($rqlQueryString);

    /**
     * Кодировать Query объект в RQL строку
     * 
     * @param Query $query
     * @return string
     */
    public static function rqlEncode($query);

    /**
     * Декодировать RQL строку (экземпляр)
     * 
     * @param string $rqlQueryString
     * @return Query
     */
    public function decode($rqlQueryString);

    /**
     * Кодировать Query объект (экземпляр)
     * 
     * @param Query $query
     * @return string
     */
    public function encode(Query $query);
}
```

### RqlQuery

Расширенный Query с поддержкой GROUP BY.

```php
class RqlQuery extends Query
{
    /**
     * @var GroupbyNode $groupBy
     */
    protected $groupBy;

    /**
     * @param mixed $query RQL строка или Query объект
     */
    public function __construct($query = null);

    /**
     * Установить группировку
     * 
     * @param GroupbyNode $groupBy
     * @return RqlQuery
     */
    public function setGroupBy(GroupbyNode $groupBy);

    /**
     * Получить группировку
     * 
     * @return GroupbyNode
     */
    public function getGroupBy();
}
```

## Middleware Components

### DataStoreApi

Основной middleware для HTTP API.

```php
class DataStoreApi implements MiddlewareInterface
{
    /**
     * @param Determinator $determinator
     * @param RequestHandlerInterface|null $renderer
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        Determinator $determinator,
        RequestHandlerInterface $renderer = null,
        LoggerInterface $logger = null
    );

    /**
     * @var MiddlewarePipe $middlewarePipe
     */
    protected $middlewarePipe;

    /**
     * @var LoggerInterface|null $logger
     */
    protected $logger;

    /**
     * Обработать HTTP запрос
     * 
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface;
}
```

### DataStoreRest

REST обработчики для различных HTTP методов.

```php
class DataStoreRest implements MiddlewareInterface
{
    /**
     * @param DataStoresInterface $dataStore
     */
    public function __construct(private DataStoresInterface $dataStore);

    /**
     * @var MiddlewarePipe $middlewarePipe
     */
    protected $middlewarePipe;
}
```

### RequestDecoder

Middleware для декодирования HTTP запросов.

```php
class RequestDecoder implements MiddlewareInterface
{
    /**
     * Обработать HTTP запрос
     * 
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface;

    /**
     * Парсинг режима перезаписи
     * 
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     */
    protected function parseOverwriteMode(ServerRequestInterface $request);

    /**
     * Парсинг RQL запроса
     * 
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     */
    protected function parseRqlQuery(ServerRequestInterface $request);

    /**
     * Парсинг лимита из заголовков
     * 
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     */
    protected function parseHeaderLimit(ServerRequestInterface $request);

    /**
     * Парсинг тела запроса
     * 
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     */
    protected function parseRequestBody(ServerRequestInterface $request);
}
```

### ResourceResolver

Middleware для определения ресурса из URL.

```php
class ResourceResolver implements MiddlewareInterface
{
    /**
     * Обработать HTTP запрос
     * 
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface;
}
```

### Determinator

Middleware для выбора подходящего DataStore.

```php
class Determinator implements MiddlewareInterface
{
    /**
     * @param DataStorePluginManager $dataStorePluginManager
     */
    public function __construct(DataStorePluginManager $dataStorePluginManager);

    /**
     * @var DataStorePluginManager $dataStorePluginManager
     */
    protected $dataStorePluginManager;

    /**
     * Обработать HTTP запрос
     * 
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface;
}
```

## Repository Components

### ModelRepository

Основной репозиторий для работы с моделями.

```php
class ModelRepository implements ModelRepositoryInterface
{
    /**
     * @param DataStoreAbstract $dataStore
     * @param string $modelClass
     * @param FieldMapperInterface $mapper
     * @param LoggerInterface $logger
     */
    public function __construct(
        DataStoreAbstract $dataStore,
        string $modelClass,
        FieldMapperInterface $mapper = null,
        LoggerInterface $logger
    );

    /**
     * @var DataStoreAbstract $dataStore
     */
    protected $dataStore;

    /**
     * @var string $modelClass
     */
    protected $modelClass;

    /**
     * @var FieldMapperInterface $mapper
     */
    protected $mapper;

    /**
     * @var LoggerInterface $logger
     */
    protected $logger;

    /**
     * Сохранить модель
     * 
     * @param ModelInterface $model
     * @return bool
     */
    public function save(ModelInterface $model): bool;

    /**
     * Сохранить несколько моделей
     * 
     * @param array $models
     * @return mixed
     */
    public function multiSave(array $models);

    /**
     * Найти модели по запросу
     * 
     * @param Query $query
     * @return array
     */
    public function find(Query $query): array;

    /**
     * Найти модель по ID
     * 
     * @param mixed $id
     * @return ModelInterface|null
     */
    public function findById($id): ?ModelInterface;

    /**
     * Удалить модель
     * 
     * @param ModelInterface $model
     * @return bool
     */
    public function remove(ModelInterface $model): bool;

    /**
     * Удалить модель по ID
     * 
     * @param mixed $id
     * @return bool
     */
    public function removeById($id): bool;

    /**
     * Получить количество записей
     * 
     * @return int
     */
    public function count(): int;

    /**
     * Получить DataStore
     * 
     * @return DataStoreAbstract
     */
    public function getDataStore();

    /**
     * Проверить существование записи
     * 
     * @param mixed $id
     * @return bool
     */
    public function has($id): bool;
}
```

### ModelAbstract

Абстрактная модель с базовой функциональностью.

```php
abstract class ModelAbstract implements ModelInterface, ModelHiddenFieldInterface, ArrayAccess
{
    use ModelArrayAccess;
    use ModelDataTime;
    use ModelCastingTrait;

    /**
     * @var array $attributes Атрибуты модели
     */
    protected $attributes = [];

    /**
     * @var array $original Оригинальные атрибуты
     */
    protected $original = [];

    /**
     * @var bool $exists Существует ли модель в хранилище
     */
    protected $exists = false;

    /**
     * @var array $casting Правила приведения типов
     */
    protected $casting = [];

    /**
     * @param array $attributes
     * @param bool $exists
     */
    public function __construct($attributes = [], $exists = false);

    /**
     * Заполнить атрибуты
     * 
     * @param array $attributes
     * @return void
     */
    public function fill($attributes);

    /**
     * Получить атрибут
     * 
     * @param string $name
     * @return mixed
     */
    public function getAttribute($name);

    /**
     * Установить атрибут
     * 
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function setAttribute($name, $value);

    /**
     * Проверить наличие атрибута
     * 
     * @param string $name
     * @return bool
     */
    public function hasAttribute($name);

    /**
     * Преобразовать в массив
     * 
     * @return array
     */
    public function toArray();

    /**
     * Установить флаг существования
     * 
     * @param bool $exists
     * @return void
     */
    public function setExists($exists);

    /**
     * Проверить существование
     * 
     * @return bool
     */
    public function isExists();

    /**
     * Проверить изменения
     * 
     * @return bool
     */
    public function isChanged();
}
```

## Uploader Components

### Uploader

Основной класс для загрузки данных.

```php
class Uploader
{
    /**
     * @param Traversable $sourceDataIteratorAggregator
     * @param DataStoresInterface $destinationDataStore
     */
    public function __construct(
        Traversable $sourceDataIteratorAggregator,
        DataStoresInterface $destinationDataStore
    );

    /**
     * @var Traversable $sourceDataIteratorAggregator
     */
    protected $sourceDataIteratorAggregator;

    /**
     * @var DataStoresInterface $destinationDataStore
     */
    protected $destinationDataStore;

    /**
     * @var mixed $key Позиция итератора
     */
    protected $key = null;

    /**
     * Выполнить загрузку
     * 
     * @return void
     */
    public function upload();

    /**
     * Вызвать загрузку
     * 
     * @param mixed $v
     * @return void
     */
    public function __invoke($v = null);
}
```

### DataStorePack

Итератор для пакетной обработки данных из DataStore.

```php
class DataStorePack implements SeekableIterator
{
    /**
     * @param DataStoresInterface $dataStore
     * @param int $limit
     */
    public function __construct(DataStoresInterface $dataStore, protected $limit = 100);

    /**
     * @var DataStoresInterface $dataStore
     */
    protected $dataStore;

    /**
     * @var array $current Текущая запись
     */
    protected $current = null;

    /**
     * Получить текущую запись
     * 
     * @return mixed
     */
    public function current();

    /**
     * Перейти к следующей записи
     * 
     * @return void
     */
    public function next();

    /**
     * Получить ключ текущей записи
     * 
     * @return mixed
     */
    public function key();

    /**
     * Проверить валидность позиции
     * 
     * @return bool
     */
    public function valid();

    /**
     * Сбросить итератор
     * 
     * @return void
     */
    public function rewind();

    /**
     * Перейти к позиции
     * 
     * @param mixed $position
     * @return void
     * @throws InvalidArgumentException
     */
    public function seek($position);
}
```

## Exceptions

### DataStoreException

Базовое исключение для DataStore.

```php
class DataStoreException extends \Exception
{
    // Наследует все методы от Exception
}
```

### ConnectionException

Исключение для ошибок подключения.

```php
class ConnectionException extends DataStoreException
{
    // Наследует все методы от DataStoreException
}
```

### InvalidArgumentException

Исключение для неверных аргументов.

```php
class InvalidArgumentException extends \InvalidArgumentException
{
    // Наследует все методы от InvalidArgumentException
}
```
