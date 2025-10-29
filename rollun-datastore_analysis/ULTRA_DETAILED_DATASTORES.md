# Ультра-детальный анализ DataStore классов

## DataStoreAbstract - базовый класс

**Класс**: `rollun\datastore\DataStore\DataStoreAbstract`
**Файл**: `src/DataStore/src/DataStore/DataStoreAbstract.php`
**Реализует**: `DataStoresInterface`, `DataStoreInterface`

### Свойства:
- `protected $conditionBuilder` - построитель условий для RQL

### Основные методы:

```php
<?php
namespace rollun\datastore\DataStore;

use rollun\datastore\DataStore\ConditionBuilder\ConditionBuilderAbstract;
use rollun\datastore\DataStore\Interfaces\DataStoreInterface;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use rollun\datastore\DataStore\Iterators\DataStoreIterator;
use rollun\datastore\Rql\Node\AggregateFunctionNode;
use rollun\datastore\Rql\Node\AggregateSelectNode;
use rollun\datastore\Rql\RqlQuery;
use Xiag\Rql\Parser\Node;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\EqNode;
use Xiag\Rql\Parser\Node\SortNode;
use Xiag\Rql\Parser\Query;

abstract class DataStoreAbstract implements DataStoresInterface, DataStoreInterface
{
    protected $conditionBuilder;

    // Основные методы интерфейса
    public function has($id): bool
    {
        return !(empty($this->read($id)));
    }

    public function read($id)
    {
        $identifier = $this->getIdentifier();
        $this->checkIdentifierType($id);
        $query = new Query();
        $eqNode = new EqNode($identifier, $id);
        $query->setQuery($eqNode);
        $queryResult = $this->query($query);
        
        if (empty($queryResult)) {
            return null;
        }
        
        return $queryResult[0];
    }

    public function getIdentifier(): string
    {
        return static::DEF_ID; // 'id'
    }

    public function query(Query $query): array
    {
        $limitNode = $query->getLimit();
        $limit = !$limitNode ? self::LIMIT_INFINITY : $query->getLimit()->getLimit();
        $offset = !$limitNode ? 0 : $query->getLimit()->getOffset();
        
        $data = $this->queryWhere($query, self::LIMIT_INFINITY, 0);
        $result = $this->querySort($data, $query);
        
        if ($query instanceof RqlQuery && $query->getGroupBy() != null) {
            $result = $this->queryGroupBy($result, $query);
        } else {
            $result = $this->querySelect($result, $query);
        }
        
        $result = array_slice($result, $offset, $limit == self::LIMIT_INFINITY ? null : $limit);
        
        // Заполнение отсутствующих полей null
        $itemFiled = [];
        foreach ($result as &$item) {
            $keys = array_keys($item);
            $diff = array_diff($keys, $itemFiled);
            $itemFiled = array_merge($itemFiled, $diff);
            $diff = array_diff($itemFiled, $keys);
            
            foreach ($diff as $field) {
                $item[$field] = null;
            }
        }
        
        return $result;
    }

    // Абстрактные методы для реализации в наследниках
    abstract public function create($itemData, $rewriteIfExist = false);
    abstract public function update($itemData, $createIfAbsent = false);
    abstract public function delete($id);

    // Реализованные методы
    public function multiCreate($records): array
    {
        $ids = [];
        foreach ($records as $record) {
            try {
                $createdRecord = $this->create($record);
                $ids[] = $createdRecord[$this->getIdentifier()];
            } catch (\Throwable) {
                continue;
            }
        }
        return $ids;
    }

    public function multiUpdate($records): array
    {
        $ids = [];
        foreach ($records as $record) {
            try {
                $updatedRecord = $this->update($record);
                $ids[] = $updatedRecord[$this->getIdentifier()];
            } catch (\Throwable) {
                continue;
            }
        }
        return $ids;
    }

    public function queriedUpdate($record, Query $query): array
    {
        $identifier = $this->getIdentifier();
        
        if (isset($record[$identifier])) {
            throw new DataStoreException('Primary key is not allowed in record for queried update');
        }
        
        $forUpdateRecords = $this->query($query);
        $updatedIds = [];
        
        foreach ($forUpdateRecords as $forUpdateRecord) {
            try {
                $updatedRecord = $this->update(array_merge($record, [$identifier => $forUpdateRecord[$identifier]]));
                $updatedIds[] = $updatedRecord[$identifier];
            } catch (\Throwable) {
                continue;
            }
        }
        
        return $updatedIds;
    }

    public function deleteAll(): ?int
    {
        $keys = $this->getKeys();
        $deletedItemsNumber = 0;
        
        foreach ($keys as $id) {
            $deletedItems = $this->delete($id);
            
            if (is_null($deletedItems)) {
                return null;
            }
            
            $deletedItemsNumber++;
        }
        
        return $deletedItemsNumber;
    }

    public function rewrite($record): array
    {
        if (!isset($record[$this->getIdentifier()])) {
            throw new DataStoreException("Identifier is required for 'rewrite' action");
        }
        
        $rewriteIfExist = false;
        
        if ($this->has($record[$this->getIdentifier()])) {
            $rewriteIfExist = true;
        }
        
        return $this->create($record, $rewriteIfExist);
    }

    public function multiRewrite($records): array
    {
        $ids = [];
        
        foreach ($records as $record) {
            if (!isset($record[$this->getIdentifier()])) {
                throw new DataStoreException("Identifier is required in 'multiRewrite' action for each record");
            }
            
            try {
                $rewroteRecord = $this->rewrite($record);
                $ids[] = $rewroteRecord[$this->getIdentifier()];
            } catch (\Throwable) {
                continue;
            }
        }
        
        return $ids;
    }

    public function queriedDelete(Query $query): array
    {
        $identifier = $this->getIdentifier();
        $queryResult = $this->query($query);
        $deletedIds = [];
        
        foreach ($queryResult as $record) {
            try {
                $deletedRecord = $this->delete($record[$identifier]);
                $deletedIds[] = $deletedRecord[$identifier];
            } catch (\Throwable) {
                continue;
            }
        }
        
        return $deletedIds;
    }

    public function count(): int
    {
        $keys = $this->getKeys();
        return count($keys);
    }

    public function getIterator(): \Traversable
    {
        trigger_error("Datastore is no more iterable", E_USER_DEPRECATED);
        return new DataStoreIterator($this);
    }

    // Вспомогательные методы
    protected function getKeys(): array
    {
        $identifier = $this->getIdentifier();
        
        $query = new Query();
        $selectNode = new Node\SelectNode([$identifier]);
        $query->setSelect($selectNode);
        $queryResult = $this->query($query);
        $keysArray = [];
        
        foreach ($queryResult as $row) {
            $keysArray[] = $row[$identifier];
        }
        
        return $keysArray;
    }

    protected function checkIdentifierType($id)
    {
        $idType = gettype($id);
        
        if ($idType === 'integer' || $idType === 'double' || $idType === 'string') {
            return;
        }
        
        throw new DataStoreException("Type of Identifier is wrong - " . $idType);
    }

    protected function wasCalledFrom(string $class, string $methodName): bool
    {
        $backtrace = debug_backtrace();
        
        return isset($backtrace[2]['function'])
            && $backtrace[2]['function'] === $methodName
            && $backtrace[2]['class'] === $class;
    }
}
```

## Memory DataStore - хранение в памяти

**Класс**: `rollun\datastore\DataStore\Memory`
**Файл**: `src/DataStore/src/DataStore/Memory.php`
**Наследует**: `DataStoreAbstract`

### Свойства:
- `protected $items = []` - массив для хранения данных
- `protected $columns = []` - массив обязательных колонок

### Конструктор:
```php
public function __construct(array $columns = [])
{
    if (!count($columns)) {
        trigger_error("Array of required columns is not specified", E_USER_DEPRECATED);
    }
    
    $this->columns = $columns;
    $this->conditionBuilder = new PhpConditionBuilder();
}
```

### Основные методы:

```php
<?php
namespace rollun\datastore\DataStore;

use rollun\datastore\DataStore\ConditionBuilder\PhpConditionBuilder;

class Memory extends DataStoreAbstract
{
    protected $items = [];
    protected $columns = [];

    public function read($id): ?array
    {
        $this->checkIdentifierType($id);
        if (isset($this->items[$id])) {
            return $this->items[$id];
        } else {
            return null;
        }
    }

    public function create($itemData, $rewriteIfExist = false): array
    {
        if (!$this->wasCalledFrom(DataStoreAbstract::class, 'rewrite')
            && !$this->wasCalledFrom(DataStoreAbstract::class, 'rewriteMultiple')
            && $rewriteIfExist
        ) {
            trigger_error("Option 'rewriteIfExist' is no more use", E_USER_DEPRECATED);
        }
        
        $this->checkOnExistingColumns($itemData);
        $identifier = $this->getIdentifier();
        $id = $itemData[$identifier] ?? null;
        
        if ($id) {
            if (isset($this->items[$id]) && !$rewriteIfExist) {
                throw new DataStoreException("Item with id '{$itemData[$identifier]}' already exist");
            }
            
            $this->checkIdentifierType($id);
        } else {
            $this->items[] = $itemData;
            $itemsKeys = array_keys($this->items);
            $id = array_pop($itemsKeys);
        }
        
        $this->items[$id] = array_merge([$identifier => $id], $itemData);
        
        return $this->items[$id];
    }

    public function update($itemData, $createIfAbsent = false): array
    {
        if ($createIfAbsent) {
            trigger_error("Option 'createIfAbsent' is no more use", E_USER_DEPRECATED);
        }
        
        $this->checkOnExistingColumns($itemData);
        $identifier = $this->getIdentifier();
        
        if (!isset($itemData[$identifier])) {
            throw new DataStoreException('Item must has primary key');
        }
        
        $this->checkIdentifierType($itemData[$identifier]);
        $id = $itemData[$identifier];
        
        if (isset($this->items[$id])) {
            foreach ($itemData as $field => $value) {
                $this->items[$id][$field] = $value;
            }
            
            unset($itemData[$id]);
        } else {
            if ($createIfAbsent) {
                $this->items[$id] = $itemData;
            } else {
                throw new DataStoreException("Item doesn't exist with id = $id");
            }
        }
        
        return $this->items[$id];
    }

    public function delete($id): ?array
    {
        $this->checkIdentifierType($id);
        
        if (isset($this->items[$id])) {
            $item = $this->items[$id];
            unset($this->items[$id]);
            
            return $item;
        }
        
        return null;
    }

    public function deleteAll(): int
    {
        $deletedItemsCount = count($this->items);
        $this->items = [];
        
        return $deletedItemsCount;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): \Traversable
    {
        trigger_error("Datastore is no more iterable", E_USER_DEPRECATED);
        return new \ArrayIterator($this->items);
    }

    protected function getKeys(): array
    {
        return array_keys($this->items);
    }

    protected function checkOnExistingColumns($itemData)
    {
        if (!count($this->columns)) {
            return $itemData;
        }
        
        foreach ($itemData as $field => $value) {
            if (!in_array($field, $this->columns)) {
                throw new DataStoreException("Undefined field '$field' in data store");
            }
        }
        
        return $itemData;
    }
}
```

## DbTable DataStore - таблицы БД

**Класс**: `rollun\datastore\DataStore\DbTable`
**Файл**: `src/DataStore/src/DataStore/DbTable.php`
**Наследует**: `DataStoreAbstract`

### Константы:
- `LOG_METHOD = 'method'`
- `LOG_TABLE = 'table'`
- `LOG_TIME = 'time'`
- `LOG_REQUEST = 'request'`
- `LOG_RESPONSE = 'response'`
- `LOG_ROLLBACK = 'rollbackTransaction'`
- `LOG_SQL = 'sql'`
- `LOG_COUNT = 'count'`

### Свойства:
- `protected $dbTable` - TableGateway для работы с БД
- `protected $sqlQueryBuilder` - построитель SQL запросов
- `protected $writeLogs` - флаг записи логов
- `protected $loggerService` - сервис логирования

### Конструктор:
```php
public function __construct(
    TableGateway $dbTable,
    bool $writeLogs = false,
    ?LoggerInterface $loggerService = null
) {
    $this->dbTable = $dbTable;
    $this->writeLogs = $writeLogs;
    $this->loggerService = $loggerService ?? new NullLogger();
}
```

### Основные методы:

```php
<?php
namespace rollun\datastore\DataStore;

use Laminas\Db\Adapter\Exception\RuntimeException;
use Laminas\Db\Sql\Predicate\In;
use Laminas\Db\Sql\Where;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use rollun\datastore\DataStore\ConditionBuilder\SqlConditionBuilder;
use rollun\datastore\Rql\RqlQuery;
use rollun\datastore\TableGateway\DbSql\MultiInsertSql;
use rollun\datastore\TableGateway\SqlQueryBuilder;
use rollun\dic\InsideConstruct;
use Xiag\Rql\Parser\Node\LimitNode;
use Xiag\Rql\Parser\Node\Query\ArrayOperator\InNode;
use Xiag\Rql\Parser\Node\SelectNode;
use Xiag\Rql\Parser\Query;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\Adapter\ParameterContainer;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Sql;
use Laminas\Db\TableGateway\TableGateway;

class DbTable extends DataStoreAbstract
{
    protected $dbTable;
    protected $sqlQueryBuilder;
    protected $writeLogs;
    protected $loggerService;

    protected function getSqlQueryBuilder()
    {
        if ($this->sqlQueryBuilder == null) {
            $this->sqlQueryBuilder = new SqlQueryBuilder($this->dbTable->getAdapter(), $this->dbTable->table);
        }
        
        return $this->sqlQueryBuilder;
    }

    public function create($itemData, $rewriteIfExist = false): array
    {
        if (!$this->wasCalledFrom(DataStoreAbstract::class, 'rewrite')
            && !$this->wasCalledFrom(DataStoreAbstract::class, 'rewriteMultiple')
            && $rewriteIfExist
        ) {
            trigger_error("Option 'rewriteIfExist' is no more use", E_USER_DEPRECATED);
        }
        
        $identifier = $this->getIdentifier();
        $id = $itemData[$identifier] ?? null;
        
        if ($id) {
            $this->checkIdentifierType($id);
            
            if (isset($itemData[$identifier]) && $this->has($id) && !$rewriteIfExist) {
                throw new DataStoreException("Item with id '{$itemData[$identifier]}' already exist");
            }
        } else {
            trigger_error("Autoincrement 'id' is not allowed", E_USER_DEPRECATED);
        }
        
        try {
            $this->dbTable->insert($itemData);
            
            if ($id) {
                $result = $this->read($id);
            } else {
                $result = $this->read($this->dbTable->getLastInsertValue());
            }
            
            if ($this->writeLogs) {
                $this->loggerService->info('DataStore create', [
                    self::LOG_METHOD => 'create',
                    self::LOG_TABLE => $this->dbTable->getTable(),
                    self::LOG_REQUEST => $itemData,
                    self::LOG_RESPONSE => $result,
                ]);
            }
            
            return $result;
        } catch (RuntimeException $e) {
            throw new DataStoreException("Can't create item. Reason: {$e->getMessage()}", 0, $e);
        }
    }

    public function update($itemData, $createIfAbsent = false): array
    {
        if ($createIfAbsent) {
            trigger_error("Option 'createIfAbsent' is no more use.", E_USER_DEPRECATED);
        }
        
        $identifier = $this->getIdentifier();
        
        if (!isset($itemData[$identifier])) {
            throw new DataStoreException('Item must has primary key');
        }
        
        $this->checkIdentifierType($itemData[$identifier]);
        $id = $itemData[$identifier];
        
        if (!$this->has($id)) {
            if ($createIfAbsent) {
                return $this->create($itemData);
            } else {
                throw new DataStoreException("Item doesn't exist with id = $id");
            }
        }
        
        try {
            $this->dbTable->update($itemData, [$identifier => $id]);
            $result = $this->read($id);
            
            if ($this->writeLogs) {
                $this->loggerService->info('DataStore update', [
                    self::LOG_METHOD => 'update',
                    self::LOG_TABLE => $this->dbTable->getTable(),
                    self::LOG_REQUEST => $itemData,
                    self::LOG_RESPONSE => $result,
                ]);
            }
            
            return $result;
        } catch (RuntimeException $e) {
            throw new DataStoreException("Can't update item. Reason: {$e->getMessage()}", 0, $e);
        }
    }

    public function delete($id): ?array
    {
        $this->checkIdentifierType($id);
        
        if (!$this->has($id)) {
            return null;
        }
        
        $item = $this->read($id);
        
        try {
            $this->dbTable->delete([$this->getIdentifier() => $id]);
            
            if ($this->writeLogs) {
                $this->loggerService->info('DataStore delete', [
                    self::LOG_METHOD => 'delete',
                    self::LOG_TABLE => $this->dbTable->getTable(),
                    self::LOG_REQUEST => $id,
                    self::LOG_RESPONSE => $item,
                ]);
            }
            
            return $item;
        } catch (RuntimeException $e) {
            throw new DataStoreException("Can't delete item. Reason: {$e->getMessage()}", 0, $e);
        }
    }

    public function query(Query $query): array
    {
        $sqlQueryBuilder = $this->getSqlQueryBuilder();
        $sqlQuery = $sqlQueryBuilder->buildSqlQuery($query);
        
        try {
            $result = $this->dbTable->getAdapter()->query($sqlQuery, Adapter::QUERY_MODE_EXECUTE);
            
            if ($this->writeLogs) {
                $this->loggerService->info('DataStore query', [
                    self::LOG_METHOD => 'query',
                    self::LOG_TABLE => $this->dbTable->getTable(),
                    self::LOG_SQL => $sqlQuery,
                    self::LOG_COUNT => $result->count(),
                ]);
            }
            
            return $result->toArray();
        } catch (RuntimeException $e) {
            throw new DataStoreException("Can't execute query. Reason: {$e->getMessage()}", 0, $e);
        }
    }
}
```

## HttpClient DataStore - HTTP API

**Класс**: `rollun\datastore\DataStore\HttpClient`
**Файл**: `src/DataStore/src/DataStore/HttpClient.php`
**Наследует**: `DataStoreAbstract`

### Константы:
- `DATASTORE_IDENTIFIER_HEADER = 'X_DATASTORE_IDENTIFIER'`

### Свойства:
- `protected $url` - URL внешнего API
- `protected $login` - логин для аутентификации
- `protected $password` - пароль для аутентификации
- `protected $client` - Laminas HTTP клиент
- `protected $options` - опции клиента
- `protected $lifeCycleToken` - токен жизненного цикла
- `protected $identifier` - имя поля-идентификатора

### Конструктор:
```php
public function __construct(
    Client $client,
    $url,
    $options = [],
    LifeCycleToken $lifeCycleToken = null
) {
    $this->client = $client;
    $this->url = $url;
    $this->options = $options;
    $this->lifeCycleToken = $lifeCycleToken;
    $this->conditionBuilder = new RqlConditionBuilder();
}
```

### Основные методы:

```php
<?php
namespace rollun\datastore\DataStore;

use Laminas\Http\Client;
use rollun\datastore\DataStore\ConditionBuilder\RqlConditionBuilder;
use rollun\datastore\Rql\RqlParser;
use Xiag\Rql\Parser\Query;

class HttpClient extends DataStoreAbstract
{
    protected $url;
    protected $login;
    protected $password;
    protected $client;
    protected $options;
    protected $lifeCycleToken;
    protected $identifier;

    public function create($itemData, $rewriteIfExist = false): array
    {
        if (!$this->wasCalledFrom(DataStoreAbstract::class, 'rewrite')
            && !$this->wasCalledFrom(DataStoreAbstract::class, 'rewriteMultiple')
            && $rewriteIfExist
        ) {
            trigger_error("Option 'rewriteIfExist' is no more use", E_USER_DEPRECATED);
        }
        
        $this->client->setMethod('POST');
        $this->client->setUri($this->url);
        $this->client->setRawBody(json_encode($itemData));
        $this->client->setHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ]);
        
        if ($rewriteIfExist) {
            $this->client->setHeaders(['If-Match' => '*']);
        }
        
        $response = $this->client->send();
        
        if ($response->getStatusCode() >= 400) {
            throw new DataStoreException("HTTP error: {$response->getStatusCode()} - {$response->getBody()}");
        }
        
        return json_decode($response->getBody(), true);
    }

    public function update($itemData, $createIfAbsent = false): array
    {
        if ($createIfAbsent) {
            trigger_error("Option 'createIfAbsent' is no more use.", E_USER_DEPRECATED);
        }
        
        $identifier = $this->getIdentifier();
        
        if (!isset($itemData[$identifier])) {
            throw new DataStoreException('Item must has primary key');
        }
        
        $this->client->setMethod('PUT');
        $this->client->setUri($this->url . '/' . $itemData[$identifier]);
        $this->client->setRawBody(json_encode($itemData));
        $this->client->setHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ]);
        
        $response = $this->client->send();
        
        if ($response->getStatusCode() >= 400) {
            throw new DataStoreException("HTTP error: {$response->getStatusCode()} - {$response->getBody()}");
        }
        
        return json_decode($response->getBody(), true);
    }

    public function delete($id): ?array
    {
        $this->checkIdentifierType($id);
        
        $this->client->setMethod('DELETE');
        $this->client->setUri($this->url . '/' . $id);
        $this->client->setHeaders(['Accept' => 'application/json']);
        
        $response = $this->client->send();
        
        if ($response->getStatusCode() >= 400) {
            throw new DataStoreException("HTTP error: {$response->getStatusCode()} - {$response->getBody()}");
        }
        
        if ($response->getStatusCode() === 204) {
            return null;
        }
        
        return json_decode($response->getBody(), true);
    }

    public function query(Query $query): array
    {
        $rqlString = RqlParser::rqlEncode($query);
        
        $this->client->setMethod('GET');
        $this->client->setUri($this->url . '?' . $rqlString);
        $this->client->setHeaders(['Accept' => 'application/json']);
        
        $response = $this->client->send();
        
        if ($response->getStatusCode() >= 400) {
            throw new DataStoreException("HTTP error: {$response->getStatusCode()} - {$response->getBody()}");
        }
        
        return json_decode($response->getBody(), true);
    }

    public function read($id)
    {
        $this->checkIdentifierType($id);
        
        $this->client->setMethod('GET');
        $this->client->setUri($this->url . '/' . $id);
        $this->client->setHeaders(['Accept' => 'application/json']);
        
        $response = $this->client->send();
        
        if ($response->getStatusCode() === 404) {
            return null;
        }
        
        if ($response->getStatusCode() >= 400) {
            throw new DataStoreException("HTTP error: {$response->getStatusCode()} - {$response->getBody()}");
        }
        
        return json_decode($response->getBody(), true);
    }
}
```

## CsvBase DataStore - CSV файлы

**Класс**: `rollun\datastore\DataStore\CsvBase`
**Файл**: `src/DataStore/src/DataStore/CsvBase.php`
**Наследует**: `DataStoreAbstract`

### Константы:
- `MAX_FILE_SIZE_FOR_CACHE = 1048576` (1MB)
- `MAX_LOCK_TRIES = 10`
- `DEFAULT_DELIMITER = ','`

### Свойства:
- `protected $csvDelimiter` - разделитель CSV
- `protected $columns` - массив колонок
- `protected $file` - SplFileObject для работы с файлом

### Конструктор:
```php
public function __construct($filename, $csvDelimiter = null)
{
    $this->csvDelimiter = $csvDelimiter ?? self::DEFAULT_DELIMITER;
    $this->conditionBuilder = new PhpConditionBuilder();
    
    if (!file_exists($filename)) {
        throw new DataStoreException("File '$filename' does not exist");
    }
    
    $this->file = new SplFileObject($filename, 'r');
    $this->file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);
    $this->file->setCsvControl($this->csvDelimiter);
    
    $this->columns = $this->file->fgetcsv();
    $this->file->rewind();
}
```

### Основные методы:

```php
<?php
namespace rollun\datastore\DataStore;

use rollun\datastore\DataStore\ConditionBuilder\PhpConditionBuilder;
use SplFileObject;

class CsvBase extends DataStoreAbstract
{
    protected $csvDelimiter;
    protected $columns;
    protected $file;

    public function create($itemData, $rewriteIfExist = false): array
    {
        if (!$this->wasCalledFrom(DataStoreAbstract::class, 'rewrite')
            && !$this->wasCalledFrom(DataStoreAbstract::class, 'rewriteMultiple')
            && $rewriteIfExist
        ) {
            trigger_error("Option 'rewriteIfExist' is no more use", E_USER_DEPRECATED);
        }
        
        $identifier = $this->getIdentifier();
        $id = $itemData[$identifier] ?? null;
        
        if ($id) {
            $this->checkIdentifierType($id);
            
            if ($this->has($id) && !$rewriteIfExist) {
                throw new DataStoreException("Item with id '{$itemData[$identifier]}' already exist");
            }
        } else {
            $id = $this->getNextId();
        }
        
        $itemData = array_merge([$identifier => $id], $itemData);
        $this->flush($itemData);
        
        return $itemData;
    }

    public function update($itemData, $createIfAbsent = false): array
    {
        if ($createIfAbsent) {
            trigger_error("Option 'createIfAbsent' is no more use.", E_USER_DEPRECATED);
        }
        
        $identifier = $this->getIdentifier();
        
        if (!isset($itemData[$identifier])) {
            throw new DataStoreException('Item must has primary key');
        }
        
        $this->checkIdentifierType($itemData[$identifier]);
        $id = $itemData[$identifier];
        
        if (!$this->has($id)) {
            if ($createIfAbsent) {
                return $this->create($itemData);
            } else {
                throw new DataStoreException("Item doesn't exist with id = $id");
            }
        }
        
        $this->flush($itemData, true);
        
        return $itemData;
    }

    public function delete($id): ?array
    {
        $this->checkIdentifierType($id);
        
        if (!$this->has($id)) {
            return null;
        }
        
        $item = $this->read($id);
        $this->flush($item, true);
        
        return $item;
    }

    public function read($id): ?array
    {
        $this->checkIdentifierType($id);
        
        foreach ($this->file as $index => $row) {
            if ($index === 0) continue; // Пропуск заголовков
            
            $row = $this->getTrueRow($row);
            if ($row[$this->getIdentifier()] == $id) {
                return $row;
            }
        }
        
        return null;
    }

    public function query(Query $query): array
    {
        $result = [];
        
        foreach ($this->file as $index => $row) {
            if ($index === 0) continue; // Пропуск заголовков
            
            $row = $this->getTrueRow($row);
            $result[] = $row;
        }
        
        return parent::query($query);
    }

    protected function flush($item, bool $delete = false): void
    {
        // Создание временного файла
        $tmpFile = tempnam(sys_get_temp_dir(), uniqid() . '.tmp');
        $tempHandler = fopen($tmpFile, 'w');
        
        // Запись заголовков
        fputcsv($tempHandler, $this->columns, $this->csvDelimiter);
        
        $identifier = $this->getIdentifier();
        $inserted = false;
        
        foreach ($this->file as $index => $row) {
            if ($index === 0) continue;
            
            $row = $this->getTrueRow($row);
            
            if ($delete && $row[$identifier] == $item[$identifier]) {
                continue; // Пропуск удаляемой записи
            }
            
            if (!$inserted && $row[$identifier] >= $item[$identifier]) {
                fputcsv($tempHandler, $item, $this->csvDelimiter);
                $inserted = true;
            }
            
            fputcsv($tempHandler, $row, $this->csvDelimiter);
        }
        
        if (!$inserted) {
            fputcsv($tempHandler, $item, $this->csvDelimiter);
        }
        
        fclose($tempHandler);
        
        // Замена оригинального файла
        rename($tmpFile, $this->file->getPathname());
        
        // Переоткрытие файла
        $this->file = new SplFileObject($this->file->getPathname(), 'r');
        $this->file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);
        $this->file->setCsvControl($this->csvDelimiter);
    }

    protected function getTrueRow($row): array
    {
        $result = [];
        foreach ($this->columns as $index => $column) {
            $result[$column] = $row[$index] ?? null;
        }
        return $result;
    }

    protected function getNextId(): int
    {
        $maxId = 0;
        foreach ($this->file as $index => $row) {
            if ($index === 0) continue;
            
            $row = $this->getTrueRow($row);
            $maxId = max($maxId, (int)$row[$this->getIdentifier()]);
        }
        return $maxId + 1;
    }
}
```

## Заключение

Каждый DataStore класс реализует интерфейсы `DataStoresInterface` и `DataStoreInterface`, предоставляя единый API для работы с различными хранилищами данных:

1. **Memory** - хранение в оперативной памяти с проверкой колонок
2. **DbTable** - работа с таблицами БД через Laminas TableGateway
3. **HttpClient** - взаимодействие с внешними HTTP API
4. **CsvBase** - работа с CSV файлами с блокировкой и временными файлами

Все классы используют RQL для запросов и поддерживают CRUD операции с единообразным API.





