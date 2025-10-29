# Примеры использования rollun-datastore

## Содержание

- [Базовые примеры DataStore](#базовые-примеры-datastore)
- [RQL запросы](#rql-запросы)
- [HTTP API](#http-api)
- [Repository паттерн](#repository-паттерн)
- [Uploader](#uploader)
- [Middleware](#middleware)
- [Продвинутые примеры](#продвинутые-примеры)

## Базовые примеры DataStore

### Memory DataStore

```php
<?php

use rollun\datastore\DataStore\Memory;
use Xiag\Rql\Parser\Query;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\EqNode;
use Xiag\Rql\Parser\Node\SortNode;
use Xiag\Rql\Parser\Node\LimitNode;

// Создание Memory DataStore
$dataStore = new Memory(['id', 'name', 'email', 'age']);

// Создание записи
$record = $dataStore->create([
    'id' => 1,
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30
]);

echo "Создана запись: " . json_encode($record) . "\n";

// Чтение записи
$record = $dataStore->read(1);
echo "Прочитана запись: " . json_encode($record) . "\n";

// Обновление записи
$updatedRecord = $dataStore->update([
    'id' => 1,
    'name' => 'John Smith',
    'email' => 'john.smith@example.com',
    'age' => 31
]);
echo "Обновлена запись: " . json_encode($updatedRecord) . "\n";

// Поиск с RQL
$query = new Query();
$query->setQuery(new EqNode('age', 31));
$results = $dataStore->query($query);
echo "Найдено записей: " . count($results) . "\n";

// Сортировка и лимит
$query = new Query();
$query->setSort(new SortNode(['name' => SortNode::SORT_ASC]));
$query->setLimit(new LimitNode(10));
$results = $dataStore->query($query);

// Удаление записи
$deleted = $dataStore->delete(1);
echo "Запись удалена: " . ($deleted ? 'да' : 'нет') . "\n";
```

### DbTable DataStore

```php
<?php

use rollun\datastore\DataStore\DbTable;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Db\Adapter\Adapter;

// Создание адаптера БД
$adapter = new Adapter([
    'driver' => 'Pdo_Mysql',
    'database' => 'my_database',
    'username' => 'my_user',
    'password' => 'my_password',
    'hostname' => 'localhost',
    'port' => 3306,
]);

// Создание TableGateway
$tableGateway = new TableGateway('users', $adapter);

// Создание DbTable DataStore
$dataStore = new DbTable($tableGateway, true, $logger);

// Создание записи
$record = $dataStore->create([
    'name' => 'Jane Doe',
    'email' => 'jane@example.com',
    'age' => 25
]);

// Массовое создание
$records = [
    ['name' => 'Alice', 'email' => 'alice@example.com', 'age' => 28],
    ['name' => 'Bob', 'email' => 'bob@example.com', 'age' => 32],
    ['name' => 'Charlie', 'email' => 'charlie@example.com', 'age' => 29],
];
$createdIds = $dataStore->multiCreate($records);
echo "Создано записей: " . count($createdIds) . "\n";

// Сложный запрос
$query = new Query();
$query->setQuery(new EqNode('age', 30));
$query->setSort(new SortNode(['name' => SortNode::SORT_ASC]));
$query->setLimit(new LimitNode(5));
$results = $dataStore->query($query);
```

### CsvBase DataStore

```php
<?php

use rollun\datastore\DataStore\CsvBase;

// Создание CSV DataStore
$csvStore = new CsvBase('/path/to/users.csv', ';');

// Создание записи
$record = $csvStore->create([
    'id' => 1,
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30
]);

// Чтение всех записей
$query = new Query();
$allRecords = $csvStore->query($query);
echo "Всего записей в CSV: " . count($allRecords) . "\n";

// Поиск по условию
$query = new Query();
$query->setQuery(new EqNode('age', 30));
$results = $csvStore->query($query);
```

### HttpClient DataStore

```php
<?php

use rollun\datastore\DataStore\HttpClient;
use Laminas\Http\Client;
use rollun\logger\LifeCycleToken;

// Создание HTTP клиента
$client = new Client();

// Создание HttpClient DataStore
$httpStore = new HttpClient(
    $client,
    'https://api.example.com/users',
    [
        'timeout' => 30,
        'maxredirects' => 5,
    ],
    new LifeCycleToken()
);

// Создание записи через HTTP API
$record = $httpStore->create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30
]);

// Поиск через HTTP API
$query = new Query();
$query->setQuery(new EqNode('age', 30));
$results = $httpStore->query($query);
```

## RQL запросы

### Базовые операторы

```php
<?php

use rollun\datastore\Rql\RqlParser;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\EqNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\NeNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\LtNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\GtNode;
use Xiag\Rql\Parser\Node\Query\ArrayOperator\InNode;
use Xiag\Rql\Parser\Node\Query\ArrayOperator\OutNode;
use Xiag\Rql\Parser\Node\Query\LogicOperator\AndNode;
use Xiag\Rql\Parser\Node\Query\LogicOperator\OrNode;

// Простые условия
$query = RqlParser::rqlDecode('eq(name,John)');
$query = RqlParser::rqlDecode('ne(age,30)');
$query = RqlParser::rqlDecode('lt(age,30)');
$query = RqlParser::rqlDecode('gt(age,18)');

// Массивные операторы
$query = RqlParser::rqlDecode('in(age,(25,30,35))');
$query = RqlParser::rqlDecode('out(status,(inactive,deleted))');

// Логические операторы
$query = RqlParser::rqlDecode('and(eq(status,active),gt(age,18))');
$query = RqlParser::rqlDecode('or(eq(name,John),eq(name,Jane))');

// Сложные условия
$query = RqlParser::rqlDecode('and(eq(status,active),or(gt(age,18),eq(role,admin)))');
```

### Специальные операторы

```php
<?php

use rollun\datastore\Rql\Node\LikeGlobNode;
use rollun\datastore\Rql\Node\AlikeGlobNode;
use rollun\datastore\Rql\Node\ContainsNode;
use rollun\datastore\Rql\Node\MatchNode;

// LIKE с подстановочными символами
$query = RqlParser::rqlDecode('like(name,John*)');
$query = RqlParser::rqlDecode('like(email,*@example.com)');

// LIKE без учета регистра
$query = RqlParser::rqlDecode('alike(name,john*)');

// Содержит подстроку
$query = RqlParser::rqlDecode('contains(description,important)');

// Регулярное выражение
$query = RqlParser::rqlDecode('match(email,^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$)');
```

### Сортировка и лимиты

```php
<?php

use Xiag\Rql\Parser\Node\SortNode;
use Xiag\Rql\Parser\Node\LimitNode;

// Сортировка
$query = RqlParser::rqlDecode('sort(+name,-age)'); // По имени ASC, по возрасту DESC

// Лимит
$query = RqlParser::rqlDecode('limit(10)'); // Первые 10 записей
$query = RqlParser::rqlDecode('limit(10,20)'); // 10 записей начиная с 20

// Комбинированный запрос
$query = RqlParser::rqlDecode('eq(status,active)&sort(+name)&limit(10)');
```

### Агрегация

```php
<?php

use rollun\datastore\Rql\Node\AggregateSelectNode;
use rollun\datastore\Rql\Node\AggregateFunctionNode;
use rollun\datastore\Rql\Node\GroupbyNode;

// Простые агрегатные функции
$query = RqlParser::rqlDecode('select(count(*),sum(age),avg(age),min(age),max(age))');

// Группировка
$query = RqlParser::rqlDecode('groupby(status)&select(count(*),avg(age))');

// Сложная агрегация
$query = RqlParser::rqlDecode('groupby(category,status)&select(count(*),sum(price),avg(price))&sort(+category)');
```

### Программное создание запросов

```php
<?php

use Xiag\Rql\Parser\Query;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\EqNode;
use Xiag\Rql\Parser\Node\Query\LogicOperator\AndNode;
use Xiag\Rql\Parser\Node\SortNode;
use Xiag\Rql\Parser\Node\LimitNode;

// Создание запроса программно
$query = new Query();

// Добавление условий
$query->setQuery(new AndNode([
    new EqNode('status', 'active'),
    new EqNode('age', 30)
]));

// Добавление сортировки
$query->setSort(new SortNode(['name' => SortNode::SORT_ASC]));

// Добавление лимита
$query->setLimit(new LimitNode(10));

// Выполнение запроса
$results = $dataStore->query($query);
```

## HTTP API

### Создание API сервера

```php
<?php

use rollun\datastore\Middleware\DataStoreApi;
use rollun\datastore\Middleware\Determinator;
use rollun\datastore\DataStore\DataStorePluginManager;
use Laminas\Stratigility\MiddlewarePipe;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\Response\JsonResponse;

// Создание DataStore PluginManager
$dataStorePluginManager = new DataStorePluginManager($container);

// Создание Determinator
$determinator = new Determinator($dataStorePluginManager);

// Создание DataStoreApi
$api = new DataStoreApi($determinator);

// Создание middleware pipe
$app = new MiddlewarePipe();
$app->pipe($api);

// Обработка запроса
$request = ServerRequestFactory::fromGlobals();
$response = $app->process($request, $handler);
```

### REST API примеры

```bash
# GET - получение всех записей
curl "http://localhost:8000/api/users"

# GET - получение записи по ID
curl "http://localhost:8000/api/users/1"

# GET - поиск с RQL
curl "http://localhost:8000/api/users?rql=eq(status,active)&sort(+name)&limit(10)"

# POST - создание записи
curl -X POST "http://localhost:8000/api/users" \
  -H "Content-Type: application/json" \
  -d '{"name":"John Doe","email":"john@example.com","age":30}'

# PUT - обновление записи
curl -X PUT "http://localhost:8000/api/users/1" \
  -H "Content-Type: application/json" \
  -d '{"id":1,"name":"John Smith","email":"john.smith@example.com","age":31}'

# DELETE - удаление записи
curl -X DELETE "http://localhost:8000/api/users/1"

# HEAD - получение метаданных
curl -I "http://localhost:8000/api/users"
```

### Обработка ошибок

```php
<?php

use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\TextResponse;

try {
    $response = $api->process($request, $handler);
} catch (\Exception $e) {
    $accept = $request->getHeader('Accept');
    
    if (in_array('application/json', $accept)) {
        $response = new JsonResponse([
            'error' => $e->getMessage(),
            'code' => $e->getCode(),
        ], 500);
    } else {
        $response = new TextResponse($e->getMessage(), 500);
    }
}
```

## Repository паттерн

### Создание модели

```php
<?php

use rollun\repository\ModelAbstract;

class User extends ModelAbstract
{
    protected $fillable = ['name', 'email', 'age', 'status'];
    protected $hidden = ['password'];
    protected $casts = [
        'age' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    public function isAdult()
    {
        return $this->age >= 18;
    }
    
    public function getFullNameAttribute()
    {
        return $this->name . ' (' . $this->email . ')';
    }
}
```

### Создание репозитория

```php
<?php

use rollun\repository\ModelRepository;
use rollun\repository\Interfaces\FieldMapperInterface;

class UserFieldMapper implements FieldMapperInterface
{
    public function map(array $data): array
    {
        // Преобразование данных при чтении
        if (isset($data['created_at'])) {
            $data['created_at'] = new \DateTime($data['created_at']);
        }
        
        return $data;
    }
}

// Создание репозитория
$userRepository = new ModelRepository(
    $dataStore,
    User::class,
    new UserFieldMapper(),
    $logger
);
```

### Работа с моделями

```php
<?php

// Создание новой модели
$user = new User([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30,
    'status' => 'active'
]);

// Сохранение модели
$saved = $userRepository->save($user);
echo "Модель сохранена: " . ($saved ? 'да' : 'нет') . "\n";

// Поиск по ID
$foundUser = $userRepository->findById(1);
if ($foundUser) {
    echo "Найден пользователь: " . $foundUser->name . "\n";
    echo "Совершеннолетний: " . ($foundUser->isAdult() ? 'да' : 'нет') . "\n";
}

// Поиск с RQL
$query = new Query();
$query->setQuery(new EqNode('status', 'active'));
$activeUsers = $userRepository->find($query);
echo "Активных пользователей: " . count($activeUsers) . "\n";

// Обновление модели
$user->name = 'John Smith';
$user->age = 31;
$updated = $userRepository->save($user);

// Удаление модели
$deleted = $userRepository->remove($user);
```

### Массовые операции

```php
<?php

// Создание нескольких моделей
$users = [
    new User(['name' => 'Alice', 'email' => 'alice@example.com', 'age' => 25]),
    new User(['name' => 'Bob', 'email' => 'bob@example.com', 'age' => 30]),
    new User(['name' => 'Charlie', 'email' => 'charlie@example.com', 'age' => 35]),
];

// Массовое сохранение
$savedIds = $userRepository->multiSave($users);
echo "Сохранено моделей: " . count($savedIds) . "\n";

// Получение всех моделей
$allUsers = $userRepository->all();
echo "Всего пользователей: " . count($allUsers) . "\n";
```

## Uploader

### Базовое использование

```php
<?php

use rollun\uploader\Uploader;
use rollun\uploader\Iterator\DataStorePack;

// Создание итератора для источника данных
$sourceIterator = new DataStorePack($sourceDataStore, 100);

// Создание Uploader
$uploader = new Uploader($sourceIterator, $destinationDataStore);

// Выполнение загрузки
$uploader->upload();
```

### Загрузка из CSV в БД

```php
<?php

use rollun\datastore\DataStore\CsvBase;
use rollun\datastore\DataStore\DbTable;
use rollun\uploader\Iterator\DataStorePack;
use rollun\uploader\Uploader;

// Создание CSV DataStore
$csvStore = new CsvBase('/path/to/users.csv', ';');

// Создание DB DataStore
$dbStore = new DbTable($tableGateway);

// Создание итератора
$iterator = new DataStorePack($csvStore, 50);

// Создание Uploader
$uploader = new Uploader($iterator, $dbStore);

// Выполнение загрузки
$uploader->upload();
```

### Загрузка с обработкой

```php
<?php

class ProcessedUploader extends Uploader
{
    public function upload()
    {
        foreach ($this->sourceDataIteratorAggregator as $key => $value) {
            // Обработка данных перед загрузкой
            $processedValue = $this->processData($value);
            
            // Загрузка обработанных данных
            $this->destinationDataStore->create($processedValue, true);
        }
    }
    
    private function processData($data)
    {
        // Добавление timestamp
        $data['imported_at'] = date('Y-m-d H:i:s');
        
        // Валидация email
        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $data['email'] = null;
        }
        
        return $data;
    }
}
```

## Middleware

### Создание кастомного middleware

```php
<?php

use rollun\datastore\Middleware\DataStoreAbstract;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LoggingMiddleware extends DataStoreAbstract
{
    protected function canHandle(ServerRequestInterface $request): bool
    {
        return true; // Обрабатывать все запросы
    }
    
    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        $startTime = microtime(true);
        
        // Логирование запроса
        $this->logger->info('DataStore request', [
            'method' => $request->getMethod(),
            'uri' => $request->getUri()->getPath(),
            'query' => $request->getUri()->getQuery(),
        ]);
        
        // Обработка запроса
        $response = $this->dataStore->query($this->getQueryFromRequest($request));
        
        // Логирование времени выполнения
        $executionTime = microtime(true) - $startTime;
        $this->logger->info('DataStore response', [
            'execution_time' => $executionTime,
            'result_count' => count($response),
        ]);
        
        return new JsonResponse($response);
    }
}
```

### Создание кастомного обработчика

```php
<?php

use rollun\datastore\Middleware\Handler\AbstractHandler;
use Laminas\Diactoros\Response\JsonResponse;

class CustomHandler extends AbstractHandler
{
    protected function canHandle(ServerRequestInterface $request): bool
    {
        return $request->getMethod() === 'PATCH' && 
               strpos($request->getUri()->getPath(), '/custom/') === 0;
    }
    
    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        $data = $request->getParsedBody();
        $query = $request->getAttribute('rqlQueryObject');
        
        // Кастомная логика обработки
        $result = $this->processCustomRequest($data, $query);
        
        return new JsonResponse($result);
    }
    
    private function processCustomRequest($data, $query)
    {
        // Реализация кастомной логики
        return ['status' => 'processed', 'data' => $data];
    }
}
```

## Продвинутые примеры

### Кэшируемый DataStore

```php
<?php

use rollun\datastore\DataStore\Cacheable;
use rollun\datastore\DataSource\DbTableDataSource;

// Создание источника данных
$dataSource = new DbTableDataSource($tableGateway, 'users');

// Создание кэшируемого DataStore
$cacheableStore = new Cacheable($dataSource, $memoryStore);

// Использование с автоматическим кэшированием
$query = new Query();
$query->setQuery(new EqNode('status', 'active'));
$results = $cacheableStore->query($query); // Результат будет закэширован
```

### Аспект с логированием

```php
<?php

use rollun\datastore\DataStore\Aspect\AspectWithEventManagerAbstract;
use Laminas\EventManager\Event;

class LoggingAspect extends AspectWithEventManagerAbstract
{
    public function __construct(DataStoreInterface $dataStore)
    {
        parent::__construct($dataStore);
        
        // Добавление слушателей событий
        $this->eventManager->attach('onPreCreate', [$this, 'logPreCreate']);
        $this->eventManager->attach('onPostCreate', [$this, 'logPostCreate']);
        $this->eventManager->attach('onPreUpdate', [$this, 'logPreUpdate']);
        $this->eventManager->attach('onPostUpdate', [$this, 'logPostUpdate']);
    }
    
    public function logPreCreate(Event $event)
    {
        $this->logger->info('Pre-create', [
            'data' => $event->getParam('data'),
        ]);
    }
    
    public function logPostCreate(Event $event)
    {
        $this->logger->info('Post-create', [
            'result' => $event->getParam('result'),
        ]);
    }
    
    public function logPreUpdate(Event $event)
    {
        $this->logger->info('Pre-update', [
            'data' => $event->getParam('data'),
        ]);
    }
    
    public function logPostUpdate(Event $event)
    {
        $this->logger->info('Post-update', [
            'result' => $event->getParam('result'),
        ]);
    }
}
```

### Транзакционный DataStore

```php
<?php

use rollun\datastore\DataStore\DbTable;
use Laminas\Db\Adapter\Adapter;

class TransactionalDataStore extends DbTable
{
    public function create($itemData, $rewriteIfExist = false)
    {
        $this->dbTable->getAdapter()->getDriver()->getConnection()->beginTransaction();
        
        try {
            $result = parent::create($itemData, $rewriteIfExist);
            $this->dbTable->getAdapter()->getDriver()->getConnection()->commit();
            return $result;
        } catch (\Exception $e) {
            $this->dbTable->getAdapter()->getDriver()->getConnection()->rollback();
            throw $e;
        }
    }
    
    public function multiCreate($records)
    {
        $this->dbTable->getAdapter()->getDriver()->getConnection()->beginTransaction();
        
        try {
            $result = parent::multiCreate($records);
            $this->dbTable->getAdapter()->getDriver()->getConnection()->commit();
            return $result;
        } catch (\Exception $e) {
            $this->dbTable->getAdapter()->getDriver()->getConnection()->rollback();
            throw $e;
        }
    }
}
```

### Валидация данных

```php
<?php

use rollun\datastore\DataStore\Memory;

class ValidatingDataStore extends Memory
{
    protected $validationRules = [];
    
    public function setValidationRules(array $rules)
    {
        $this->validationRules = $rules;
    }
    
    public function create($itemData, $rewriteIfExist = false)
    {
        $this->validateData($itemData);
        return parent::create($itemData, $rewriteIfExist);
    }
    
    public function update($itemData, $createIfAbsent = false)
    {
        $this->validateData($itemData);
        return parent::update($itemData, $createIfAbsent);
    }
    
    private function validateData($data)
    {
        foreach ($this->validationRules as $field => $rules) {
            if (!isset($data[$field])) {
                if (in_array('required', $rules)) {
                    throw new \InvalidArgumentException("Field '{$field}' is required");
                }
                continue;
            }
            
            $value = $data[$field];
            
            foreach ($rules as $rule) {
                switch ($rule) {
                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            throw new \InvalidArgumentException("Field '{$field}' must be a valid email");
                        }
                        break;
                    case 'integer':
                        if (!is_int($value) && !ctype_digit($value)) {
                            throw new \InvalidArgumentException("Field '{$field}' must be an integer");
                        }
                        break;
                    case 'string':
                        if (!is_string($value)) {
                            throw new \InvalidArgumentException("Field '{$field}' must be a string");
                        }
                        break;
                }
            }
        }
    }
}

// Использование
$validatingStore = new ValidatingDataStore(['id', 'name', 'email']);
$validatingStore->setValidationRules([
    'name' => ['required', 'string'],
    'email' => ['required', 'email'],
    'age' => ['integer'],
]);
```

### Мониторинг производительности

```php
<?php

use rollun\datastore\DataStore\Memory;

class ProfilingDataStore extends Memory
{
    protected $metrics = [];
    
    public function query(Query $query)
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        $result = parent::query($query);
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        $this->metrics[] = [
            'operation' => 'query',
            'execution_time' => $endTime - $startTime,
            'memory_usage' => $endMemory - $startMemory,
            'result_count' => count($result),
            'timestamp' => date('Y-m-d H:i:s'),
        ];
        
        return $result;
    }
    
    public function getMetrics()
    {
        return $this->metrics;
    }
    
    public function getAverageExecutionTime()
    {
        if (empty($this->metrics)) {
            return 0;
        }
        
        $totalTime = array_sum(array_column($this->metrics, 'execution_time'));
        return $totalTime / count($this->metrics);
    }
}
```
