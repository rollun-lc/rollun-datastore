# Troubleshooting - rollun-datastore

## Содержание

- [Общие проблемы](#общие-проблемы)
- [DataStore проблемы](#datastore-проблемы)
- [RQL проблемы](#rql-проблемы)
- [HTTP API проблемы](#http-api-проблемы)
- [Repository проблемы](#repository-проблемы)
- [Конфигурация проблемы](#конфигурация-проблемы)
- [Производительность](#производительность)
- [Отладка](#отладка)

## Общие проблемы

### Ошибка: "Class not found"

**Проблема:** PHP не может найти классы библиотеки.

**Причины:**
- Не установлен autoloader
- Неправильная конфигурация PSR-4
- Отсутствуют зависимости

**Решение:**
```bash
# Установка через Composer
composer install

# Проверка autoloader
composer dump-autoload

# Проверка конфигурации в composer.json
{
    "autoload": {
        "psr-4": {
            "rollun\\datastore\\": "src/DataStore/src",
            "rollun\\uploader\\": "src/Uploader/src",
            "rollun\\repository\\": "src/Repository/src"
        }
    }
}
```

### Ошибка: "Service not found in container"

**Проблема:** ServiceManager не может найти сервис.

**Причины:**
- Сервис не зарегистрирован в конфигурации
- Неправильное имя сервиса
- Отсутствует Abstract Factory

**Решение:**
```php
// Проверка конфигурации
$config = $container->get('config');
var_dump($config['dataStore']); // Проверить наличие DataStore

// Регистрация сервиса вручную
$container->setService('myDataStore', $dataStore);

// Проверка Abstract Factory
$config = $container->get('config');
if (!isset($config['dependencies']['abstract_factories'])) {
    $config['dependencies']['abstract_factories'][] = DataStoreAbstractFactory::class;
}
```

### Ошибка: "Invalid configuration"

**Проблема:** Неверная конфигурация DataStore.

**Причины:**
- Отсутствует обязательный параметр 'class'
- Неправильный формат конфигурации
- Отсутствуют зависимости

**Решение:**
```php
// Минимальная конфигурация DataStore
'dataStore' => [
    'myStore' => [
        'class' => 'rollun\datastore\DataStore\Memory', // Обязательно
        'requiredColumns' => ['id', 'name'], // Для Memory
    ],
],

// Проверка конфигурации
if (!isset($storeConfig['class'])) {
    throw new \InvalidArgumentException('class is required for DataStore configuration');
}
```

## DataStore проблемы

### DbTable: "Table not found"

**Проблема:** Таблица не существует в базе данных.

**Причины:**
- Таблица не создана
- Неправильное имя таблицы
- Проблемы с правами доступа

**Решение:**
```php
// Создание таблицы через TableManagerMysql
$tableManager = $container->get('TableManagerMysql');
$tableManager->createTable('users', [
    'id' => [
        'type' => 'INT',
        'options' => ['AUTO_INCREMENT', 'PRIMARY KEY'],
    ],
    'name' => [
        'type' => 'VARCHAR(255)',
        'options' => ['NOT NULL'],
    ],
]);

// Проверка существования таблицы
$adapter = $container->get('db');
$tables = $adapter->query("SHOW TABLES LIKE 'users'")->execute();
if ($tables->count() === 0) {
    throw new \Exception('Table users does not exist');
}
```

### DbTable: "Connection failed"

**Проблема:** Не удается подключиться к базе данных.

**Причины:**
- Неверные параметры подключения
- База данных недоступна
- Проблемы с сетью

**Решение:**
```php
// Проверка параметров подключения
$config = [
    'driver' => 'Pdo_Mysql',
    'database' => 'my_database',
    'username' => 'my_user',
    'password' => 'my_password',
    'hostname' => 'localhost',
    'port' => 3306,
];

// Тест подключения
try {
    $adapter = new Adapter($config);
    $adapter->getDriver()->getConnection()->connect();
    echo "Connection successful\n";
} catch (\Exception $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}

// Использование переменных окружения
$config = [
    'driver' => getenv('DB_DRIVER') ?: 'Pdo_Mysql',
    'database' => getenv('DB_NAME'),
    'username' => getenv('DB_USER'),
    'password' => getenv('DB_PASS'),
    'hostname' => getenv('DB_HOST'),
    'port' => getenv('DB_PORT') ?: 3306,
];
```

### Memory: "Required columns not specified"

**Проблема:** Не указаны обязательные колонки для Memory DataStore.

**Причины:**
- Отсутствует параметр 'requiredColumns'
- Пустой массив колонок

**Решение:**
```php
// Правильная конфигурация Memory DataStore
$dataStore = new Memory(['id', 'name', 'email']);

// Или через конфигурацию
'dataStore' => [
    'memoryStore' => [
        'class' => 'rollun\datastore\DataStore\Memory',
        'requiredColumns' => ['id', 'name', 'email'],
    ],
],

// Проверка в коде
if (empty($this->columns)) {
    throw new \InvalidArgumentException('Required columns must be specified for Memory DataStore');
}
```

### CsvBase: "File not found"

**Проблема:** CSV файл не найден.

**Причины:**
- Неправильный путь к файлу
- Файл не существует
- Проблемы с правами доступа

**Решение:**
```php
// Проверка существования файла
$filename = '/path/to/file.csv';
if (!file_exists($filename)) {
    // Попытка найти в temp директории
    $tempFilename = sys_get_temp_dir() . DIRECTORY_SEPARATOR . basename($filename);
    if (!file_exists($tempFilename)) {
        throw new \Exception("CSV file not found: {$filename}");
    }
    $filename = $tempFilename;
}

// Проверка прав доступа
if (!is_readable($filename)) {
    throw new \Exception("CSV file is not readable: {$filename}");
}

// Создание файла если не существует
if (!file_exists($filename)) {
    $dir = dirname($filename);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    touch($filename);
}
```

### HttpClient: "HTTP request failed"

**Проблема:** HTTP запрос завершился с ошибкой.

**Причины:**
- Неверный URL
- Проблемы с сетью
- Ошибки аутентификации
- Таймаут

**Решение:**
```php
// Настройка HTTP клиента с обработкой ошибок
$client = new Client();
$client->setOptions([
    'timeout' => 30,
    'maxredirects' => 5,
    'useragent' => 'MyApp/1.0',
]);

// Обработка ошибок
try {
    $response = $client->send();
    if (!$response->isSuccess()) {
        throw new \Exception("HTTP request failed: " . $response->getStatusCode());
    }
} catch (\Exception $e) {
    echo "HTTP error: " . $e->getMessage() . "\n";
}

// Проверка URL
$url = 'https://api.example.com/datastore';
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    throw new \InvalidArgumentException("Invalid URL: {$url}");
}
```

## RQL проблемы

### RQL: "Parse error"

**Проблема:** Ошибка парсинга RQL строки.

**Причины:**
- Неправильный синтаксис RQL
- Неподдерживаемые операторы
- Проблемы с кодировкой

**Решение:**
```php
// Проверка синтаксиса RQL
try {
    $query = RqlParser::rqlDecode('eq(name,John)');
} catch (\Exception $e) {
    echo "RQL parse error: " . $e->getMessage() . "\n";
}

// Валидация RQL строки
function validateRql($rqlString) {
    $allowedOperators = ['eq', 'ne', 'lt', 'gt', 'le', 'ge', 'in', 'out', 'and', 'or', 'not'];
    $pattern = '/\b(' . implode('|', $allowedOperators) . ')\s*\(/';
    
    if (!preg_match($pattern, $rqlString)) {
        throw new \InvalidArgumentException("Invalid RQL operator");
    }
    
    return true;
}

// Использование
validateRql('eq(name,John)');
```

### RQL: "Unsupported operator"

**Проблема:** Использование неподдерживаемого оператора.

**Причины:**
- Оператор не реализован в ConditionBuilder
- Неправильное имя оператора

**Решение:**
```php
// Проверка поддерживаемых операторов
$supportedOperators = [
    'eq', 'ne', 'lt', 'gt', 'le', 'ge',
    'in', 'out', 'and', 'or', 'not',
    'like', 'alike', 'contains', 'match'
];

$rqlString = 'unknown(field,value)';
foreach ($supportedOperators as $operator) {
    if (strpos($rqlString, $operator . '(') !== false) {
        continue;
    }
}
// Если оператор не найден, выбросить исключение

// Создание кастомного ConditionBuilder
class CustomConditionBuilder extends ConditionBuilderAbstract
{
    public function __invoke($query)
    {
        // Реализация поддержки дополнительных операторов
    }
}
```

### RQL: "Type casting error"

**Проблема:** Ошибка приведения типов в RQL.

**Причины:**
- Неправильный тип данных
- Проблемы с TypeCaster

**Решение:**
```php
// Настройка TypeCaster
$typeCaster = new TypeCaster();
$typeCaster->registerTypeCaster('string', new StringTypeCaster());
$typeCaster->registerTypeCaster('integer', new IntegerTypeCaster());
$typeCaster->registerTypeCaster('float', new FloatTypeCaster());
$typeCaster->registerTypeCaster('boolean', new BooleanTypeCaster());

// Использование в RqlParser
$parser = new RqlParser(null, $conditionBuilder);
$parser->setTypeCaster($typeCaster);

// Валидация типов
function validateRqlValue($value, $expectedType) {
    switch ($expectedType) {
        case 'integer':
            return is_int($value) || ctype_digit($value);
        case 'float':
            return is_float($value) || is_numeric($value);
        case 'boolean':
            return is_bool($value) || in_array($value, ['true', 'false', '1', '0']);
        case 'string':
            return is_string($value);
        default:
            return true;
    }
}
```

## HTTP API проблемы

### API: "404 Not Found"

**Проблема:** API возвращает 404 ошибку.

**Причины:**
- Неправильный URL
- Отсутствует маршрут
- Проблемы с ResourceResolver

**Решение:**
```php
// Проверка URL структуры
$url = 'http://localhost:8000/api/users';
$path = parse_url($url, PHP_URL_PATH);

// Ожидаемая структура: /api/{resource}
if (!preg_match('/^\/api\/([a-zA-Z0-9_-]+)/', $path, $matches)) {
    throw new \InvalidArgumentException("Invalid API URL format");
}

$resource = $matches[1];

// Проверка наличия DataStore для ресурса
$dataStore = $dataStorePluginManager->get($resource);
if (!$dataStore) {
    throw new \Exception("DataStore not found for resource: {$resource}");
}

// Настройка маршрутизации
$app->pipe('/api', $dataStoreApi);
```

### API: "500 Internal Server Error"

**Проблема:** Внутренняя ошибка сервера.

**Причины:**
- Исключение в коде
- Проблемы с конфигурацией
- Ошибки в DataStore

**Решение:**
```php
// Обработка исключений в middleware
try {
    $response = $this->middlewarePipe->process($request, $handler);
} catch (\Exception $e) {
    // Логирование ошибки
    $this->logger->error("API error", [
        'exception' => $e,
        'request' => $request->getUri()->getPath(),
        'method' => $request->getMethod(),
    ]);
    
    // Возврат ошибки клиенту
    $accept = $request->getHeader('Accept');
    if (in_array('application/json', $accept)) {
        return new JsonResponse([
            'error' => $e->getMessage(),
            'code' => $e->getCode(),
        ], 500);
    } else {
        return new TextResponse($e->getMessage(), 500);
    }
}
```

### API: "CORS error"

**Проблема:** Ошибка CORS при запросах из браузера.

**Причины:**
- Отсутствуют CORS заголовки
- Неправильная настройка CORS

**Решение:**
```php
// Middleware для CORS
class CorsMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        
        return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }
}

// Использование
$app->pipe(new CorsMiddleware());
$app->pipe('/api', $dataStoreApi);
```

## Repository проблемы

### Repository: "Model not found"

**Проблема:** Модель не найдена по ID.

**Причины:**
- Запись не существует
- Неправильный ID
- Проблемы с DataStore

**Решение:**
```php
// Проверка существования модели
$model = $repository->findById($id);
if (!$model) {
    throw new \Exception("Model not found with ID: {$id}");
}

// Проверка DataStore
$dataStore = $repository->getDataStore();
if (!$dataStore->has($id)) {
    throw new \Exception("Record not found in DataStore");
}

// Валидация ID
if (empty($id) || !is_numeric($id)) {
    throw new \InvalidArgumentException("Invalid ID: {$id}");
}
```

### Repository: "Save failed"

**Проблема:** Не удается сохранить модель.

**Причины:**
- Проблемы с валидацией
- Ошибки в DataStore
- Проблемы с маппером

**Решение:**
```php
// Валидация модели перед сохранением
if (!$model->isValid()) {
    throw new \Exception("Model validation failed: " . implode(', ', $model->getErrors()));
}

// Проверка DataStore
try {
    $result = $dataStore->create($model->toArray());
} catch (\Exception $e) {
    throw new \Exception("DataStore error: " . $e->getMessage());
}

// Проверка маппера
if ($this->mapper) {
    try {
        $mappedData = $this->mapper->map($model->toArray());
    } catch (\Exception $e) {
        throw new \Exception("Mapper error: " . $e->getMessage());
    }
}
```

### Repository: "Field mapping error"

**Проблема:** Ошибка маппинга полей.

**Причины:**
- Неправильная конфигурация маппера
- Отсутствуют поля в данных
- Проблемы с типами данных

**Решение:**
```php
// Валидация маппера
class FieldMapper implements FieldMapperInterface
{
    protected $fieldMap = [
        'user_name' => 'name',
        'user_email' => 'email',
    ];
    
    public function map(array $data): array
    {
        $mappedData = [];
        
        foreach ($this->fieldMap as $sourceField => $targetField) {
            if (isset($data[$sourceField])) {
                $mappedData[$targetField] = $data[$sourceField];
            } else {
                // Логирование отсутствующих полей
                error_log("Field mapping: source field '{$sourceField}' not found");
            }
        }
        
        return $mappedData;
    }
}
```

## Конфигурация проблемы

### Config: "Circular dependency"

**Проблема:** Циклическая зависимость в конфигурации.

**Причины:**
- Сервисы ссылаются друг на друга
- Неправильная настройка Abstract Factory

**Решение:**
```php
// Проверка циклических зависимостей
function checkCircularDependency($config, $visited = [], $current = null) {
    if ($current === null) {
        foreach ($config['dataStore'] as $name => $storeConfig) {
            if (isset($storeConfig['dataStore']) && in_array($storeConfig['dataStore'], $visited)) {
                throw new \Exception("Circular dependency detected: {$name}");
            }
            checkCircularDependency($config, array_merge($visited, [$name]), $storeConfig['dataStore'] ?? null);
        }
    }
}

// Использование
checkCircularDependency($config);
```

### Config: "Missing dependency"

**Проблема:** Отсутствует зависимость в конфигурации.

**Причины:**
- Не зарегистрирован сервис
- Неправильное имя зависимости

**Решение:**
```php
// Проверка зависимостей
function validateDependencies($config, $container) {
    foreach ($config['dataStore'] as $name => $storeConfig) {
        if (isset($storeConfig['tableGateway'])) {
            if (!$container->has($storeConfig['tableGateway'])) {
                throw new \Exception("TableGateway not found: {$storeConfig['tableGateway']}");
            }
        }
        
        if (isset($storeConfig['dataSource'])) {
            if (!$container->has($storeConfig['dataSource'])) {
                throw new \Exception("DataSource not found: {$storeConfig['dataSource']}");
            }
        }
    }
}
```

## Производительность

### Медленные запросы

**Проблема:** Запросы выполняются медленно.

**Причины:**
- Отсутствуют индексы в БД
- Неэффективные RQL запросы
- Большие объемы данных

**Решение:**
```php
// Создание индексов
$adapter = $container->get('db');
$adapter->query("CREATE INDEX idx_status ON users(status)")->execute();
$adapter->query("CREATE INDEX idx_age ON users(age)")->execute();

// Оптимизация RQL запросов
$query = new Query();
$query->setQuery(new EqNode('status', 'active')); // Использует индекс
$query->setLimit(new LimitNode(100)); // Ограничение результатов

// Пакетная обработка
$batchSize = 100;
$offset = 0;
do {
    $query = new Query();
    $query->setLimit(new LimitNode($batchSize, $offset));
    $results = $dataStore->query($query);
    
    // Обработка батча
    processBatch($results);
    
    $offset += $batchSize;
} while (count($results) === $batchSize);
```

### Высокое потребление памяти

**Проблема:** Приложение потребляет много памяти.

**Причины:**
- Загрузка больших объемов данных
- Утечки памяти
- Неэффективные итераторы

**Решение:**
```php
// Использование итераторов
$iterator = $dataStore->getIterator();
foreach ($iterator as $record) {
    // Обработка одной записи
    processRecord($record);
    
    // Очистка памяти
    unset($record);
    if (memory_get_usage() > 100 * 1024 * 1024) { // 100MB
        gc_collect_cycles();
    }
}

// Пакетная обработка с ограничением памяти
class MemoryLimitedProcessor
{
    protected $maxMemory = 50 * 1024 * 1024; // 50MB
    
    public function process($dataStore)
    {
        $query = new Query();
        $query->setLimit(new LimitNode(1000));
        
        do {
            $results = $dataStore->query($query);
            $this->processBatch($results);
            
            // Проверка памяти
            if (memory_get_usage() > $this->maxMemory) {
                gc_collect_cycles();
            }
            
            $query->getLimit()->setOffset($query->getLimit()->getOffset() + 1000);
        } while (count($results) === 1000);
    }
}
```

## Отладка

### Включение логирования

```php
// Настройка логирования
$logger = new Logger('datastore');
$logger->pushHandler(new StreamHandler('logs/datastore.log', Logger::DEBUG));

// Логирование в DataStore
$dataStore = new DbTable($tableGateway, true, $logger);

// Логирование SQL запросов
$dataStore->setWriteLogs(true);
```

### Отладка RQL

```php
// Логирование RQL запросов
class DebugRqlParser extends RqlParser
{
    public function decode($rqlQueryString)
    {
        error_log("RQL Query: " . $rqlQueryString);
        
        try {
            $result = parent::decode($rqlQueryString);
            error_log("RQL Parsed successfully");
            return $result;
        } catch (\Exception $e) {
            error_log("RQL Parse error: " . $e->getMessage());
            throw $e;
        }
    }
}
```

### Профилирование

```php
// Профилирование DataStore операций
class ProfilingDataStore extends DataStoreAbstract
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
        ];
        
        return $result;
    }
    
    public function getMetrics()
    {
        return $this->metrics;
    }
}
```

### Тестирование

```php
// Unit тесты для DataStore
class DataStoreTest extends TestCase
{
    public function testCreate()
    {
        $dataStore = new Memory(['id', 'name']);
        $record = $dataStore->create(['id' => 1, 'name' => 'Test']);
        
        $this->assertEquals(1, $record['id']);
        $this->assertEquals('Test', $record['name']);
    }
    
    public function testQuery()
    {
        $dataStore = new Memory(['id', 'name']);
        $dataStore->create(['id' => 1, 'name' => 'John']);
        $dataStore->create(['id' => 2, 'name' => 'Jane']);
        
        $query = new Query();
        $query->setQuery(new EqNode('name', 'John'));
        $results = $dataStore->query($query);
        
        $this->assertCount(1, $results);
        $this->assertEquals('John', $results[0]['name']);
    }
}
```
