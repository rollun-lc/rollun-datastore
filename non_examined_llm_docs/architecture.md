# Архитектура rollun-datastore

## Обзор архитектуры

Библиотека `rollun-datastore` построена на принципах SOLID и использует паттерны:
- **Strategy** - различные реализации DataStore
- **Factory** - создание объектов через DI контейнер
- **Repository** - работа с моделями данных
- **Middleware** - обработка HTTP запросов
- **Aspect** - перехват вызовов для дополнительной логики

## Структура пакетов

### 1. rollun\datastore

Основной пакет, содержащий:

#### DataStore компоненты
- **Интерфейсы**: `DataStoreInterface`, `DataStoresInterface`, `ReadInterface`
- **Абстрактный класс**: `DataStoreAbstract`
- **Реализации**: `DbTable`, `Memory`, `HttpClient`, `CsvBase`, `CsvIntId`, `SerializedDbTable`, `Cacheable`

#### RQL система
- **Парсер**: `RqlParser` - парсинг RQL строк в объекты Query
- **Query**: `RqlQuery` - расширенный Query с поддержкой GROUP BY
- **Узлы**: различные узлы для условий, сортировки, лимитов

#### Middleware
- **DataStoreApi** - основной middleware для HTTP API
- **DataStoreRest** - REST обработчики
- **Handlers** - обработчики для различных HTTP методов

#### TableGateway
- **SqlQueryBuilder** - построение SQL запросов
- **TableManagerMysql** - управление таблицами MySQL

### 2. rollun\repository

Repository паттерн для работы с моделями:

#### Основные компоненты
- **ModelRepository** - основной репозиторий
- **ModelAbstract** - абстрактная модель
- **Interfaces** - интерфейсы для моделей и репозиториев
- **Traits** - трейты для функциональности моделей

### 3. rollun\uploader

Компонент для загрузки данных:

#### Основные компоненты
- **Uploader** - основной класс загрузчика
- **DataStorePack** - итератор для пакетной обработки
- **Factory** - фабрика для создания загрузчиков

## Иерархия классов

### DataStore иерархия

```
DataStoreInterface (extends ReadInterface)
├── DataStoresInterface (extends ReadInterface)
└── DataStoreAbstract (implements DataStoresInterface, DataStoreInterface)
    ├── Memory
    ├── DbTable
    ├── HttpClient
    ├── CsvBase
    ├── CsvIntId
    ├── SerializedDbTable
    └── Cacheable
```

### Middleware иерархия

```
MiddlewareInterface
├── DataStoreAbstract
│   ├── DataStoreApi
│   └── DataStoreRest
└── Handler\AbstractHandler
    ├── CreateHandler
    ├── ReadHandler
    ├── UpdateHandler
    ├── DeleteHandler
    ├── QueryHandler
    └── ErrorHandler
```

### Repository иерархия

```
ModelRepositoryInterface
└── ModelRepository

ModelInterface
└── ModelAbstract
    └── SimpleModelExtendedAbstract
```

## Основные интерфейсы

### DataStoreInterface

```php
interface DataStoreInterface extends ReadInterface
{
    public function create($record);
    public function multiCreate($records);
    public function update($record);
    public function multiUpdate($records);
    public function delete($id);
    public function multiDelete($ids);
}
```

### ReadInterface

```php
interface ReadInterface extends \Countable, \IteratorAggregate
{
    public const DEF_ID = 'id';
    public const LIMIT_INFINITY = 2147483647;
    
    public function getIdentifier();
    public function read($id);
    public function has($id);
    public function query(Query $query);
    public function count();
    public function getIterator();
}
```

### ModelRepositoryInterface

```php
interface ModelRepositoryInterface
{
    public function save(ModelInterface $model): bool;
    public function multiSave(array $models);
    public function find(Query $query): array;
    public function findById($id): ?ModelInterface;
    public function remove(ModelInterface $model): bool;
    public function removeById($id): bool;
    public function count(): int;
    public function getDataStore();
    public function has($id): bool;
}
```

## Конфигурация и DI

### ConfigProvider структура

Каждый пакет имеет свой `ConfigProvider`:

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

### Abstract Factories

Библиотека использует Abstract Factories для создания объектов:

- `DataStoreAbstractFactory` - создание DataStore
- `DbTableAbstractFactory` - создание DbTable
- `HttpClientAbstractFactory` - создание HttpClient
- `MemoryAbstractFactory` - создание Memory
- `CsvAbstractFactory` - создание CSV DataStore
- `CacheableAbstractFactory` - создание кэшируемых DataStore
- `ModelRepositoryAbstractFactory` - создание репозиториев
- `UploaderAbstractFactory` - создание загрузчиков

## RQL (Resource Query Language)

### Поддерживаемые операторы

#### Скалярные операторы
- `eq(field,value)` - равенство
- `ne(field,value)` - неравенство
- `lt(field,value)` - меньше
- `gt(field,value)` - больше
- `le(field,value)` - меньше или равно
- `ge(field,value)` - больше или равно

#### Массивные операторы
- `in(field,(value1,value2))` - входит в список
- `out(field,(value1,value2))` - не входит в список

#### Логические операторы
- `and(condition1,condition2)` - И
- `or(condition1,condition2)` - ИЛИ
- `not(condition)` - НЕ

#### Специальные операторы
- `like(field,pattern)` - LIKE с подстановочными символами
- `alike(field,pattern)` - LIKE без учета регистра
- `contains(field,value)` - содержит подстроку
- `match(field,regex)` - регулярное выражение

#### Сортировка и лимиты
- `sort(+field1,-field2)` - сортировка
- `limit(10,20)` - лимит и offset
- `select(field1,field2)` - выбор полей

#### Агрегация
- `select(count(*),sum(field),avg(field))` - агрегатные функции
- `groupby(field1,field2)` - группировка

### Примеры RQL запросов

```php
// Простой поиск
$query = RqlParser::rqlDecode('eq(name,John)');

// Сложный запрос
$query = RqlParser::rqlDecode('and(eq(status,active),gt(age,18))&sort(+name)&limit(10)');

// Агрегация
$query = RqlParser::rqlDecode('groupby(category)&select(count(*),sum(price))');
```

## Middleware архитектура

### Pipeline обработки

```
Request → ResourceResolver → RequestDecoder → Determinator → Handler
```

1. **ResourceResolver** - определение ресурса из URL
2. **RequestDecoder** - парсинг RQL и JSON из запроса
3. **Determinator** - выбор подходящего DataStore
4. **Handler** - обработка конкретного HTTP метода

### HTTP методы

- `GET` - чтение данных (QueryHandler, ReadHandler)
- `POST` - создание данных (CreateHandler, MultiCreateHandler)
- `PUT` - обновление данных (UpdateHandler, QueriedUpdateHandler)
- `DELETE` - удаление данных (DeleteHandler)
- `HEAD` - получение метаданных (HeadHandler)

## Аспекты (Aspects)

### AspectAbstract

Базовый класс для создания аспектов:

```php
abstract class AspectAbstract extends DataStoreAbstract
{
    protected $dataStore;
    
    public function __construct(DataStoreInterface $dataStore)
    {
        $this->dataStore = $dataStore;
    }
    
    // Переопределение методов с дополнительной логикой
}
```

### AspectWithEventManagerAbstract

Аспект с поддержкой событий:

```php
class AspectWithEventManagerAbstract extends AspectAbstract
{
    protected $eventManager;
    
    // Обработка событий onPreCreate, onPostCreate, etc.
}
```

## Кэширование

### Cacheable DataStore

```php
class Cacheable extends DataStoreAbstract
{
    protected $dataSource;
    protected $cacheable;
    
    // Кэширование результатов запросов
}
```

## Обработка ошибок

### Исключения

- `DataStoreException` - общие ошибки DataStore
- `ConnectionException` - ошибки подключения
- `InvalidArgumentException` - неверные аргументы

### Логирование

Все DataStore поддерживают логирование через PSR-3 Logger:

```php
$dataStore = new DbTable($tableGateway, true, $logger);
```

## Производительность

### Оптимизации

1. **Пакетная обработка** - `multiCreate`, `multiUpdate`, `multiDelete`
2. **Итераторы** - `DataStoreIterator` для больших наборов данных
3. **Кэширование** - `Cacheable` DataStore
4. **Connection pooling** - переиспользование соединений

### Мониторинг

- Логирование SQL запросов
- Измерение времени выполнения
- Счетчики операций
