# Детальный анализ эндпоинтов rollun-datastore

## Полный HTTP Pipeline

### 1. Точка входа
**Файл**: `public/test.php` (основной) / `public/index.php` (минимальный)

```php
// Создание HTTP запроса
$request = ServerRequestFactory::fromGlobals($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);

// Получение DI контейнера
$container = require 'config/container.php';

// Создание middleware pipe
$middlewarePipe = new MiddlewarePipe();
$middlewarePipe->pipe($container->get(DataStoreApi::class));

// Запуск обработки
$runner = new RequestHandlerRunner($middlewarePipe, new SapiEmitter(), $serverRequestFactory, $errorResponseGenerator);
$runner->run();
```

### 2. DataStoreApi Middleware
**Класс**: `rollun\datastore\Middleware\DataStoreApi`
**Файл**: `src/DataStore/src/Middleware/DataStoreApi.php`

```php
class DataStoreApi implements MiddlewareInterface
{
    protected $middlewarePipe;
    protected $logger;
    
    public function __construct(Determinator $determinator, RequestHandlerInterface $renderer = null, LoggerInterface $logger = null)
    {
        $this->logger = $logger;
        $this->middlewarePipe = new MiddlewarePipe();
        
        // Pipeline: ResourceResolver -> RequestDecoder -> Determinator
        $this->middlewarePipe->pipe(new ResourceResolver());
        $this->middlewarePipe->pipe(new RequestDecoder());
        $this->middlewarePipe->pipe($determinator);
    }
    
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $this->middlewarePipe->process($request, $handler);
        } catch (\Exception $e) {
            // Обработка ошибок с логированием
            if ($this->logger) {
                $this->logger->error("Exception in Datastore middleware", ['exception' => $e]);
            }
            // Возврат JSON или текстовой ошибки в зависимости от Accept заголовка
            $accept = $request->getHeader('Accept');
            if (in_array('application/json', $accept)) {
                return new JsonResponse(['error' => $e->getMessage()], 500);
            } else {
                return new TextResponse($e->getMessage(), 500);
            }
        }
    }
}
```

### 3. ResourceResolver Middleware
**Класс**: `rollun\datastore\Middleware\ResourceResolver`
**Файл**: `src/DataStore/src/Middleware/ResourceResolver.php`

```php
class ResourceResolver implements MiddlewareInterface
{
    public const BASE_PATH = '/api/datastore';
    public const RESOURCE_NAME = 'resourceName';
    public const PRIMARY_KEY_VALUE = 'primaryKeyValue';
    
    protected $basePath;
    
    public function __construct($basePath = null)
    {
        $this->basePath = $basePath ?? self::BASE_PATH;
    }
    
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getAttribute(self::RESOURCE_NAME) !== null) {
            // Router уже установил "resourceName" (работает в expressive)
            $id = empty($request->getAttribute("id")) ? null : $this->decodeString($request->getAttribute("id"));
            $request = $request->withAttribute(self::PRIMARY_KEY_VALUE, $id);
        } else {
            // "resourceName" не установлен (работает в stratigility)
            $path = $request->getUri()->getPath();
            $basePath = preg_quote(rtrim($this->basePath, '/'), '/');
            $pattern = "/{$basePath}\/([\w\~\-\_]+)([\/]([-%_A-Za-z0-9]+))?\/?$/";
            preg_match($pattern, $path, $matches);
            
            $resourceName = $matches[1] ?? null;
            $request = $request->withAttribute(self::RESOURCE_NAME, $resourceName);
            
            $id = isset($matches[3]) ? $this->decodeString($matches[3]) : null;
            $request = $request->withAttribute(self::PRIMARY_KEY_VALUE, $id);
        }
        
        return $handler->handle($request);
    }
    
    private function decodeString($value)
    {
        return rawurldecode(strtr($value, [
            '%2D' => '-', '%5F' => '_', '%2E' => '.', '%7E' => '~'
        ]));
    }
}
```

### 4. RequestDecoder Middleware
**Класс**: `rollun\datastore\Middleware\RequestDecoder`
**Файл**: `src/DataStore/src/Middleware/RequestDecoder.php`

```php
class RequestDecoder implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Парсинг всех атрибутов запроса
        $request = $this->parseOverwriteMode($request);
        $request = $this->parseRqlQuery($request);
        $request = $this->parseHeaderLimit($request);
        $request = $this->parseRequestBody($request);
        $request = $this->parseContentRange($request);
        
        return $handler->handle($request);
    }
    
    protected function parseOverwriteMode(ServerRequestInterface $request)
    {
        $overwriteModeHeader = $request->getHeader('If-Match');
        $overwriteMode = (isset($overwriteModeHeader[0]) && $overwriteModeHeader[0] === '*') ? true : false;
        return $request->withAttribute('overwriteMode', $overwriteMode);
    }
    
    protected function parseRqlQuery(ServerRequestInterface $request)
    {
        $rqlQueryStringWithXdebug = $request->getUri()->getQuery();
        $rqlQueryString = preg_replace('/\&XDEBUG_SESSION_START\=[\w\d_-]+/', "", $rqlQueryStringWithXdebug);
        $rqlQueryObject = RqlParser::rqlDecode($rqlQueryString);
        return $request->withAttribute('rqlQueryObject', $rqlQueryObject);
    }
    
    protected function parseHeaderLimit(ServerRequestInterface $request)
    {
        $headerLimit = $request->getHeader('Range');
        if (isset($headerLimit) && is_array($headerLimit) && count($headerLimit) > 0) {
            trigger_error("Header 'Range' is deprecated", E_USER_DEPRECATED);
            $match = [];
            preg_match('/^items=([0-9]+)\-?([0-9]+)?/', $headerLimit[0], $match);
            if (count($match) > 0) {
                $limit = [];
                if (isset($match[2])) {
                    $limit['offset'] = $match[1];
                    $limit['limit'] = $match[2];
                } else {
                    $limit['limit'] = $match[1];
                }
                $request = $request->withAttribute('Limit', $limit);
            }
        }
        return $request;
    }
    
    protected function parseRequestBody(ServerRequestInterface $request)
    {
        $contentTypeArray = $request->getHeader('Content-Type');
        $contentType = $contentTypeArray[0] ?? 'text/html';
        
        if (str_contains($contentType, 'json')) {
            $body = !empty($request->getBody()->__toString()) 
                ? Serializer::jsonUnserialize($request->getBody()->__toString()) 
                : null;
            $request = $request->withParsedBody($body);
        } elseif ($contentType === 'text/plain' || $contentType === 'text/html' || $contentType === 'application/x-www-form-urlencoded') {
            $request = $request->withParsedBody(null);
        } else {
            throw new RestException("Unknown Content-Type header - $contentType");
        }
        
        return $request;
    }
    
    protected function parseContentRange(ServerRequestInterface $request)
    {
        $withContentRangeHeader = $request->getHeader('With-Content-Range');
        $withContentRange = (isset($withContentRangeHeader[0]) && $withContentRangeHeader[0] === '*') ? true : false;
        return $request->withAttribute('withContentRange', $withContentRange);
    }
}
```

### 5. Determinator Middleware
**Класс**: `rollun\datastore\Middleware\Determinator`
**Файл**: `src/DataStore/src/Middleware/Determinator.php`

```php
class Determinator implements MiddlewareInterface
{
    protected $dataStorePluginManager;
    
    public function __construct(DataStorePluginManager $dataStorePluginManager)
    {
        $this->dataStorePluginManager = $dataStorePluginManager;
    }
    
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $requestedName = $request->getAttribute(ResourceResolver::RESOURCE_NAME);
        
        if (!$this->dataStorePluginManager->has($requestedName)) {
            return new EmptyResponse(404);
        }
        
        $dataStore = $this->dataStorePluginManager->get($requestedName);
        
        $dataStoreRest = new DataStoreRest($dataStore);
        $response = $dataStoreRest->process($request, $handler);
        
        $dataStoreScheme = $dataStore instanceof AspectTyped ? json_encode($dataStore->getScheme()) : '';
        
        $response = $response->withHeader('Datastore-Scheme', $dataStoreScheme);
        $response = $response->withHeader('X_DATASTORE_IDENTIFIER', $dataStore->getIdentifier());
        
        return $response;
    }
}
```

### 6. DataStoreRest Middleware
**Класс**: `rollun\datastore\Middleware\DataStoreRest`
**Файл**: `src/DataStore/src/Middleware/DataStoreRest.php`

```php
class DataStoreRest implements MiddlewareInterface
{
    protected $middlewarePipe;
    
    public function __construct(private DataStoresInterface $dataStore)
    {
        $this->middlewarePipe = new MiddlewarePipe();
        
        // Порядок Handler'ов важен - они проверяются последовательно
        $this->middlewarePipe->pipe(new Handler\HeadHandler($this->dataStore));
        $this->middlewarePipe->pipe(new Handler\DownloadCsvHandler($this->dataStore));
        $this->middlewarePipe->pipe(new Handler\QueryHandler($this->dataStore));
        $this->middlewarePipe->pipe(new Handler\ReadHandler($this->dataStore));
        $this->middlewarePipe->pipe(new Handler\MultiCreateHandler($this->dataStore));
        $this->middlewarePipe->pipe(new Handler\CreateHandler($this->dataStore));
        $this->middlewarePipe->pipe(new Handler\UpdateHandler($this->dataStore));
        $this->middlewarePipe->pipe(new Handler\RefreshHandler($this->dataStore));
        $this->middlewarePipe->pipe(new Handler\DeleteHandler($this->dataStore));
        $this->middlewarePipe->pipe(new Handler\QueriedUpdateHandler($this->dataStore));
        $this->middlewarePipe->pipe(new Handler\ErrorHandler());
    }
    
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->middlewarePipe->process($request, $handler);
    }
}
```

## Детальный анализ каждого эндпоинта

### 1. HEAD /api/datastore/{resource}
**Handler**: `rollun\datastore\Middleware\Handler\HeadHandler`
**Файл**: `src/DataStore/src/Middleware/Handler/HeadHandler.php`

```php
class HeadHandler extends AbstractHandler
{
    protected function canHandle(ServerRequestInterface $request): bool
    {
        return $request->getMethod() === "HEAD";
    }
    
    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        $response = (new JsonResponse([]))
            ->withHeader('X_DATASTORE_IDENTIFIER', $this->dataStore->getIdentifier());
        
        if (method_exists($this->dataStore, 'multiCreate')) {
            $response = $response->withHeader('X_MULTI_CREATE', 'true');
        }
        
        if (method_exists($this->dataStore, 'queriedUpdate')) {
            $response = $response->withHeader('X_QUERIED_UPDATE', 'true');
        }
        
        return $response;
    }
}
```

**Алгоритм выполнения**:
1. Проверка метода запроса === "HEAD"
2. Создание пустого JSON ответа
3. Добавление заголовка `X_DATASTORE_IDENTIFIER` с именем поля-идентификатора
4. Проверка поддержки `multiCreate` и добавление заголовка `X_MULTI_CREATE`
5. Проверка поддержки `queriedUpdate` и добавление заголовка `X_QUERIED_UPDATE`

### 2. GET /api/datastore/{resource}?download=csv
**Handler**: `rollun\datastore\Middleware\Handler\DownloadCsvHandler`
**Файл**: `src/DataStore/src/Middleware/Handler/DownloadCsvHandler.php`

```php
class DownloadCsvHandler extends AbstractHandler
{
    public const HEADER = 'download';
    public const DELIMITER = ',';
    public const ENCLOSURE = '"';
    public const ESCAPE_CHAR = '\\';
    public const LIMIT = 8000;
    
    public function canHandle(ServerRequestInterface $request): bool
    {
        if ($request->getMethod() == 'GET') {
            foreach ($request->getHeader(self::HEADER) as $item) {
                if ($item == 'csv') {
                    return true;
                }
            }
        }
        return false;
    }
    
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $dataStore = $this->dataStore;
        
        // Создание имени файла из URL
        $fileName = explode("/", $request->getUri()->getPath());
        $fileName = array_pop($fileName) . '.csv';
        
        /** @var Query $rqlQuery */
        $rqlQuery = $request->getAttribute('rqlQueryObject');
        
        // Создание CSV файла
        $file = fopen('php://temp', 'w');
        
        $offset = 0;
        $items = [1];
        
        // Пагинация по 8000 записей
        while (count($items) > 0) {
            $rqlQuery->setLimit(new LimitNode(self::LIMIT, $offset));
            $items = $dataStore->query($rqlQuery);
            
            foreach ($items as $line) {
                fputcsv($file, $line, self::DELIMITER, self::ENCLOSURE, self::ESCAPE_CHAR);
            }
            
            $offset += self::LIMIT;
        }
        
        fseek($file, 0);
        $body = new Stream($file);
        
        $response = (new Response())
            ->withHeader('Content-Type', 'text/csv')
            ->withHeader('Content-Disposition', 'attachment; filename=' . $fileName)
            ->withHeader('Content-Transfer-Encoding', 'Binary')
            ->withHeader('Content-Description', 'File Transfer')
            ->withHeader('Pragma', 'public')
            ->withHeader('Expires', '0')
            ->withHeader('Cache-Control', 'must-revalidate')
            ->withBody($body)
            ->withHeader('Content-Length', "{$body->getSize()}");
        
        return $response;
    }
}
```

**Алгоритм выполнения**:
1. Проверка метода === "GET" и заголовка `download: csv`
2. Извлечение имени ресурса из URL для имени файла
3. Получение RQL запроса из атрибутов
4. Создание временного файла
5. Пагинация по 8000 записей с применением RQL фильтрации
6. Запись данных в CSV с разделителем `,`, ограничителем `"`, экранированием `\`
7. Создание HTTP ответа с CSV заголовками

### 3. GET /api/datastore/{resource} (с RQL запросом)
**Handler**: `rollun\datastore\Middleware\Handler\QueryHandler`
**Файл**: `src/DataStore/src/Middleware/Handler/QueryHandler.php`

```php
class QueryHandler extends AbstractHandler
{
    public function canHandle(ServerRequestInterface $request): bool
    {
        $canHandle = $request->getMethod() === "GET";
        $query = $request->getAttribute('rqlQueryObject');
        
        $canHandle = $canHandle && ($query instanceof Query);
        
        $primaryKeyValue = $request->getAttribute('primaryKeyValue');
        $canHandle = $canHandle && is_null($primaryKeyValue);
        
        return $canHandle;
    }
    
    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Query $rqlQuery */
        $rqlQuery = $request->getAttribute('rqlQueryObject');
        $items = $this->dataStore->query($rqlQuery);
        
        $response = new JsonResponse($items);
        
        if ($request->getAttribute('withContentRange')) {
            $contentRange = $this->createContentRange($rqlQuery, $items);
            $response = $response->withHeader('Content-Range', $contentRange);
        }
        
        return $response;
    }
    
    protected function createContentRange(Query $rqlQuery, $items)
    {
        $limitNode = $rqlQuery->getLimit();
        $total = $this->getTotalItems($rqlQuery);
        
        if ($limitNode) {
            $offset = $limitNode->getOffset() ?? 0;
        } else {
            $offset = 0;
        }
        
        return "items " . ($offset + 1) . "-" . ($offset + count($items)) . "/$total";
    }
    
    protected function getTotalItems(Query $rqlQuery)
    {
        $rqlQuery->setLimit(new LimitNode(ReadInterface::LIMIT_INFINITY));
        $aggregateCountFunction = new AggregateFunctionNode('count', $this->dataStore->getIdentifier());
        
        $rqlQuery->setSelect(new SelectNode([$aggregateCountFunction]));
        $aggregateCount = $this->dataStore->query($rqlQuery);
        
        return current($aggregateCount)["$aggregateCountFunction"];
    }
}
```

**Алгоритм выполнения**:
1. Проверка метода === "GET" и наличия RQL запроса
2. Проверка отсутствия primaryKeyValue (не для чтения одной записи)
3. Выполнение RQL запроса через `$this->dataStore->query($rqlQuery)`
4. Создание JSON ответа с результатами
5. Если запрошен Content-Range, вычисление диапазона записей
6. Для вычисления общего количества используется агрегатная функция count

### 4. GET /api/datastore/{resource}/{id}
**Handler**: `rollun\datastore\Middleware\Handler\ReadHandler`
**Файл**: `src/DataStore/src/Middleware/Handler/ReadHandler.php`

```php
class ReadHandler extends AbstractHandler
{
    public function canHandle(ServerRequestInterface $request): bool
    {
        $canHandle = $request->getMethod() === "GET";
        
        $primaryKeyValue = $request->getAttribute('primaryKeyValue');
        $canHandle = $canHandle && isset($primaryKeyValue);
        
        return $canHandle && $this->isRqlQueryEmpty($request);
    }
    
    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        $primaryKeyValue = $request->getAttribute('primaryKeyValue');
        $items = $this->dataStore->read($primaryKeyValue);
        
        return new JsonResponse($items);
    }
}
```

**Алгоритм выполнения**:
1. Проверка метода === "GET" и наличия primaryKeyValue
2. Проверка пустого RQL запроса (только чтение одной записи)
3. Выполнение `$this->dataStore->read($primaryKeyValue)`
4. Возврат JSON ответа с записью

### 5. POST /api/datastore/{resource} (массовое создание)
**Handler**: `rollun\datastore\Middleware\Handler\MultiCreateHandler`
**Файл**: `src/DataStore/src/Middleware/Handler/MultiCreateHandler.php`

```php
class MultiCreateHandler extends AbstractHandler
{
    public function canHandle(ServerRequestInterface $request): bool
    {
        if ($request->getMethod() !== "POST") {
            return false;
        }
        
        $rows = $request->getParsedBody();
        
        // Проверка что body - массив массивов
        if (!isset($rows) || !is_array($rows) || !isset($rows[0]) || !is_array($rows[0])) {
            return false;
        }
        
        foreach ($rows as $row) {
            $canHandle = isset($row)
                && is_array($row)
                && array_reduce(
                    array_keys($row),
                    fn($carry, $item) => $carry && is_string($item),
                    true
                );
            
            if (!$canHandle) {
                return false;
            }
        }
        
        return $this->isRqlQueryEmpty($request);
    }
    
    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        $rows = $request->getParsedBody();
        
        if ($this->dataStore instanceof DataStoreInterface) {
            $result = $this->dataStore->multiCreate($rows);
        } else {
            $result = [];
            foreach ($rows as $row) {
                try {
                    $result[] = $this->dataStore->create($row);
                    usleep(10000); // 10ms задержка
                } catch (DataStoreException) {
                    // Игнорирование ошибок
                }
            }
            $result = array_column($result, $this->dataStore->getIdentifier());
        }
        
        return new JsonResponse($result, 201, ['Location' => $request->getUri()->getPath()]);
    }
}
```

**Алгоритм выполнения**:
1. Проверка метода === "POST"
2. Проверка что body - массив массивов (множественные записи)
3. Проверка что все ключи в записях - строки
4. Проверка пустого RQL запроса
5. Если DataStore поддерживает `multiCreate`, использует его
6. Иначе создает записи по одной с задержкой 10ms
7. Возврат массива ID созданных записей со статусом 201

### 6. POST /api/datastore/{resource} (создание одной записи)
**Handler**: `rollun\datastore\Middleware\Handler\CreateHandler`
**Файл**: `src/DataStore/src/Middleware/Handler/CreateHandler.php`

```php
class CreateHandler extends AbstractHandler
{
    public function canHandle(ServerRequestInterface $request): bool
    {
        $canHandle = $request->getMethod() === "POST";
        $row = $request->getParsedBody();
        
        $canHandle = $canHandle
            && isset($row)
            && is_array($row)
            && array_reduce(
                array_keys($row),
                fn($carry, $item) => $carry && is_string($item),
                true
            );
        
        return $canHandle && $this->isRqlQueryEmpty($request);
    }
    
    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        $row = $request->getParsedBody();
        $overwriteMode = $request->getAttribute('overwriteMode');
        $primaryKeyIdentifier = $this->dataStore->getIdentifier();
        $isRowExist = false;
        
        $primaryKeyValue = $row[$primaryKeyIdentifier] ?? $request->getAttribute('primaryKeyValue');
        if ($primaryKeyValue) {
            $row = array_merge([$primaryKeyIdentifier => $primaryKeyValue], $row);
        }
        
        if ($primaryKeyValue) {
            $isRowExist = !empty($this->dataStore->read($primaryKeyValue));
            
            if ($isRowExist && !$overwriteMode) {
                throw new DataStoreException("Item with id '{$primaryKeyValue}' already exist");
            }
        }
        
        $newItem = $this->dataStore->create($row, $overwriteMode);
        $response = new JsonResponse($newItem);
        
        if (!$isRowExist) {
            $response = $response->withStatus(201);
            $location = $request->getUri()->getPath();
            $response = $response->withHeader('Location', $location);
        }
        
        return $response;
    }
}
```

**Алгоритм выполнения**:
1. Проверка метода === "POST" и валидности body
2. Проверка пустого RQL запроса
3. Получение режима перезаписи из заголовка `If-Match`
4. Определение primary key из body или атрибутов
5. Проверка существования записи с таким ID
6. Если запись существует и нет режима перезаписи - исключение
7. Создание записи через `$this->dataStore->create($row, $overwriteMode)`
8. Возврат созданной записи со статусом 201 и заголовком Location

### 7. PUT /api/datastore/{resource}/{id}
**Handler**: `rollun\datastore\Middleware\Handler\UpdateHandler`
**Файл**: `src/DataStore/src/Middleware/Handler/UpdateHandler.php`

```php
class UpdateHandler extends AbstractHandler
{
    public function canHandle(ServerRequestInterface $request): bool
    {
        $canHandle = $request->getMethod() === "PUT";
        
        $primaryKeyValue = $request->getAttribute('primaryKeyValue');
        $row = $request->getParsedBody();
        
        if (!$primaryKeyValue && isset($row[$this->dataStore->getIdentifier()])) {
            $primaryKeyValue = $row[$this->dataStore->getIdentifier()];
        }
        
        $canHandle = $canHandle && isset($primaryKeyValue);
        
        $canHandle = $canHandle && isset($row) && is_array($row)
            && array_reduce(
                array_keys($row),
                fn($carry, $item) => $carry && !is_int($item),
                true
            );
        
        return $canHandle && $this->isRqlQueryEmpty($request);
    }
    
    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        $primaryKeyValue = $request->getAttribute('primaryKeyValue');
        $primaryKeyIdentifier = $this->dataStore->getIdentifier();
        $item = $request->getParsedBody();
        
        if (!$primaryKeyValue && isset($item[$this->dataStore->getIdentifier()])) {
            $primaryKeyValue = $item[$this->dataStore->getIdentifier()];
        } else {
            $item = array_merge([$primaryKeyIdentifier => $primaryKeyValue], $item);
        }
        
        $overwriteMode = $request->getAttribute('overwriteMode');
        $isItemExist = !empty($this->dataStore->read($primaryKeyValue));
        
        $newItem = $this->dataStore->update($item, $overwriteMode);
        
        $response = new JsonResponse($newItem);
        
        if ($overwriteMode && !$isItemExist) {
            $response = $response->withStatus(201);
        }
        
        return $response;
    }
}
```

**Алгоритм выполнения**:
1. Проверка метода === "PUT"
2. Определение primary key из URL или body
3. Проверка валидности body (ассоциативный массив)
4. Проверка пустого RQL запроса
5. Получение режима перезаписи
6. Проверка существования записи
7. Обновление записи через `$this->dataStore->update($item, $overwriteMode)`
8. Если режим перезаписи и запись не существовала - статус 201

### 8. PATCH /api/datastore/{resource} (обновление DataStore)
**Handler**: `rollun\datastore\Middleware\Handler\RefreshHandler`
**Файл**: `src/DataStore/src/Middleware/Handler/RefreshHandler.php`

```php
class RefreshHandler extends AbstractHandler
{
    public function canHandle(ServerRequestInterface $request): bool
    {
        return $request->getMethod() === "PATCH" && $this->isRqlQueryEmpty($request);
    }
    
    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->dataStore instanceof RefreshableInterface) {
            $this->dataStore->refresh();
            return new JsonResponse([]);
        }
        
        throw new RestException("DataStore is not implement RefreshableInterface");
    }
}
```

**Алгоритм выполнения**:
1. Проверка метода === "PATCH" и пустого RQL запроса
2. Проверка что DataStore реализует `RefreshableInterface`
3. Вызов `$this->dataStore->refresh()`
4. Возврат пустого JSON ответа

### 9. DELETE /api/datastore/{resource}/{id}
**Handler**: `rollun\datastore\Middleware\Handler\DeleteHandler`
**Файл**: `src/DataStore/src/Middleware/Handler/DeleteHandler.php`

```php
class DeleteHandler extends AbstractHandler
{
    public function canHandle(ServerRequestInterface $request): bool
    {
        $canHandle = $request->getMethod() === "DELETE";
        
        $primaryKeyValue = $request->getAttribute('primaryKeyValue');
        $canHandle = $canHandle && isset($primaryKeyValue);
        
        return $canHandle && $this->isRqlQueryEmpty($request);
    }
    
    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        $primaryKeyValue = $request->getAttribute('primaryKeyValue');
        $items = $this->dataStore->delete($primaryKeyValue);
        
        $response = new JsonResponse($items);
        
        if (!isset($items)) {
            $response = $response->withStatus(204);
        }
        
        return $response;
    }
}
```

**Алгоритм выполнения**:
1. Проверка метода === "DELETE" и наличия primaryKeyValue
2. Проверка пустого RQL запроса
3. Удаление записи через `$this->dataStore->delete($primaryKeyValue)`
4. Если запись не найдена - статус 204, иначе - возврат удаленной записи

### 10. PATCH /api/datastore/{resource} (обновление по RQL запросу)
**Handler**: `rollun\datastore\Middleware\Handler\QueriedUpdateHandler`
**Файл**: `src/DataStore/src/Middleware/Handler/QueriedUpdateHandler.php`

```php
class QueriedUpdateHandler extends AbstractHandler
{
    public function canHandle(ServerRequestInterface $request): bool
    {
        if ($request->getMethod() !== "PATCH") {
            return false;
        }
        
        if ($request->getAttribute('primaryKeyValue')) {
            return false;
        }
        
        $query = $request->getAttribute('rqlQueryObject');
        if (!($query instanceof Query) || is_null($query->getQuery())) {
            return false;
        }
        
        if ($query->getLimit() === null) {
            return false;
        }
        
        $fields = $request->getParsedBody();
        if (
            !isset($fields) ||
            !is_array($fields) ||
            array_keys($fields) === range(0, count($fields) - 1) ||
            empty($fields)
        ) {
            return false;
        }
        
        return $this->isRqlQueryNotContainsGroupByAndSelect($query);
    }
    
    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getAttribute('rqlQueryObject');
        $fields = $request->getParsedBody();
        
        if ($this->dataStore instanceof DataStoreInterface) {
            $result = $this->dataStore->queriedUpdate($fields, $query);
        } else {
            $identifier = $this->dataStore->getIdentifier();
            
            $items = $this->dataStore->query($query);
            $updated = [];
            
            foreach ($items as $item) {
                $payload = $fields;
                $payload[$identifier] = $item[$identifier];
                
                try {
                    $updated[] = $this->dataStore->update($payload);
                    usleep(10000); // 10ms
                } catch (DataStoreException) {
                    // Игнорирование ошибок
                }
            }
            
            $result = array_column($updated, $identifier);
        }
        
        return new JsonResponse($result);
    }
    
    private function isRqlQueryNotContainsGroupByAndSelect(Query $query): bool
    {
        return is_null($query->getGroupBy()) && is_null($query->getSelect());
    }
}
```

**Алгоритм выполнения**:
1. Проверка метода === "PATCH" и отсутствия primaryKeyValue
2. Проверка наличия RQL запроса с фильтром и лимитом
3. Проверка что body - ассоциативный массив с полями для обновления
4. Проверка отсутствия GROUP BY и SELECT в RQL
5. Если DataStore поддерживает `queriedUpdate`, использует его
6. Иначе выполняет запрос, получает записи и обновляет по одной
7. Возврат массива ID обновленных записей

### 11. ErrorHandler (обработка ошибок)
**Handler**: `rollun\datastore\Middleware\Handler\ErrorHandler`
**Файл**: `src/DataStore/src/Middleware/Handler/ErrorHandler.php`

```php
class ErrorHandler implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        throw new RestException(
            "No one datastore handler was executed. "
            . "Method: '{$request->getMethod()}'. "
            . "Uri: '{$request->getUri()->getPath()}'. "
            . "ParsedBody: '" . json_encode($request->getParsedBody()) . "'. "
            . "Attributes: '" . json_encode($request->getAttributes()) . "'. "
        );
    }
}
```

**Алгоритм выполнения**:
1. Вызывается если ни один Handler не смог обработать запрос
2. Выбрасывает `RestException` с детальной информацией о запросе
3. Исключение обрабатывается в `DataStoreApi` и возвращается как HTTP ошибка

## Заключение

Каждый эндпоинт проходит через полный pipeline:
1. **DataStoreApi** - основной middleware с обработкой ошибок
2. **ResourceResolver** - извлечение ресурса и ID из URL
3. **RequestDecoder** - парсинг RQL, JSON, заголовков
4. **Determinator** - выбор DataStore по имени ресурса
5. **DataStoreRest** - выбор подходящего Handler'а
6. **Handler** - выполнение конкретной операции
7. **DataStore** - выполнение операции с данными

Все Handler'ы наследуются от `AbstractHandler` и реализуют паттерн Chain of Responsibility, проверяя возможность обработки запроса в методе `canHandle()` и выполняя обработку в методе `handle()`.





