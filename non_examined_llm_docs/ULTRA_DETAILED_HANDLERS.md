# Ультра-детальный анализ Handler'ов

## AbstractHandler - базовый класс

**Класс**: `rollun\datastore\Middleware\Handler\AbstractHandler`
**Файл**: `src/DataStore/src/Middleware/Handler/AbstractHandler.php`
**Наследует**: `rollun\datastore\Middleware\DataStoreAbstract`

```php
<?php
namespace rollun\datastore\Middleware\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use rollun\datastore\Middleware\DataStoreAbstract;
use Xiag\Rql\Parser\Query;

abstract class AbstractHandler extends DataStoreAbstract
{
    abstract protected function canHandle(ServerRequestInterface $request): bool;
    abstract protected function handle(ServerRequestInterface $request): ResponseInterface;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->canHandle($request)) {
            return $this->handle($request);
        }
        return $handler->handle($request);
    }

    protected function isRqlQueryEmpty($request): bool
    {
        $query = $request->getAttribute('rqlQueryObject');
        if (!($query instanceof Query)) {
            return true;
        }
        return is_null($query->getLimit())
            && is_null($query->getSort())
            && is_null($query->getSelect())
            && is_null($query->getQuery());
    }
}
```

## DataStoreAbstract - базовый middleware

**Класс**: `rollun\datastore\Middleware\DataStoreAbstract`
**Файл**: `src/DataStore/src/Middleware/DataStoreAbstract.php`

```php
<?php
namespace rollun\datastore\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use rollun\datastore\DataStore\Interfaces\DataStoreInterface;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;

abstract class DataStoreAbstract implements MiddlewareInterface
{
    protected $dataStore;

    public function __construct($dataStore)
    {
        if ($dataStore instanceof DataStoreInterface || $dataStore instanceof DataStoresInterface) {
            $this->dataStore = $dataStore;
        } else {
            throw new \InvalidArgumentException("DataStore '$dataStore' should be instance of DataStoreInterface or DataStoresInterface");
        }
    }
}
```

## 1. HeadHandler - метаданные DataStore

**Класс**: `rollun\datastore\Middleware\Handler\HeadHandler`
**Файл**: `src/DataStore/src/Middleware/Handler/HeadHandler.php`

### Алгоритм выполнения:
1. **canHandle()**: Проверяет метод запроса === "HEAD"
2. **handle()**: 
   - Создает пустой JSON ответ
   - Добавляет заголовок `X_DATASTORE_IDENTIFIER` с именем поля-идентификатора
   - Проверяет поддержку `multiCreate` и добавляет заголовок `X_MULTI_CREATE`
   - Проверяет поддержку `queriedUpdate` и добавляет заголовок `X_QUERIED_UPDATE`

```php
<?php
namespace rollun\datastore\Middleware\Handler;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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

## 2. DownloadCsvHandler - экспорт в CSV

**Класс**: `rollun\datastore\Middleware\Handler\DownloadCsvHandler`
**Файл**: `src/DataStore/src/Middleware/Handler/DownloadCsvHandler.php`

### Константы:
- `HEADER = 'download'`
- `DELIMITER = ','`
- `ENCLOSURE = '"'`
- `ESCAPE_CHAR = '\\'`
- `LIMIT = 8000`

### Алгоритм выполнения:
1. **canHandle()**: Проверяет метод === "GET" и заголовок `download: csv`
2. **handle()**:
   - Извлекает имя ресурса из URL для имени файла
   - Получает RQL запрос из атрибутов
   - Создает временный файл
   - Пагинация по 8000 записей с применением RQL фильтрации
   - Запись данных в CSV с разделителем `,`, ограничителем `"`, экранированием `\`
   - Создание HTTP ответа с CSV заголовками

```php
<?php
namespace rollun\datastore\Middleware\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Xiag\Rql\Parser\Node\LimitNode;
use Xiag\Rql\Parser\Query;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Stream;

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
        
        // Создание имени файла
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

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->canHandle($request)) {
            return $this->handle($request);
        }
        return $handler->handle($request);
    }
}
```

## 3. QueryHandler - RQL запросы

**Класс**: `rollun\datastore\Middleware\Handler\QueryHandler`
**Файл**: `src/DataStore/src/Middleware/Handler/QueryHandler.php`

### Алгоритм выполнения:
1. **canHandle()**: 
   - Проверяет метод === "GET" и наличие RQL запроса
   - Проверяет отсутствие primaryKeyValue (не для чтения одной записи)
2. **handle()**:
   - Выполняет RQL запрос через `$this->dataStore->query($rqlQuery)`
   - Создает JSON ответ с результатами
   - Если запрошен Content-Range, вычисляет диапазон записей
   - Для вычисления общего количества используется агрегатная функция count

```php
<?php
namespace rollun\datastore\Middleware\Handler;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use rollun\datastore\DataStore\Interfaces\ReadInterface;
use rollun\datastore\Rql\Node\AggregateFunctionNode;
use Xiag\Rql\Parser\Node\LimitNode;
use Xiag\Rql\Parser\Node\SelectNode;
use Xiag\Rql\Parser\Query;

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

## 4. ReadHandler - чтение одной записи

**Класс**: `rollun\datastore\Middleware\Handler\ReadHandler`
**Файл**: `src/DataStore/src/Middleware/Handler/ReadHandler.php`

### Алгоритм выполнения:
1. **canHandle()**: 
   - Проверяет метод === "GET" и наличие primaryKeyValue
   - Проверяет пустой RQL запрос (только чтение одной записи)
2. **handle()**:
   - Выполняет `$this->dataStore->read($primaryKeyValue)`
   - Возвращает JSON ответ с записью

```php
<?php
namespace rollun\datastore\Middleware\Handler;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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

## 5. MultiCreateHandler - массовое создание

**Класс**: `rollun\datastore\Middleware\Handler\MultiCreateHandler`
**Файл**: `src/DataStore/src/Middleware/Handler/MultiCreateHandler.php`

### Алгоритм выполнения:
1. **canHandle()**: 
   - Проверяет метод === "POST"
   - Проверяет что body - массив массивов (множественные записи)
   - Проверяет что все ключи в записях - строки
   - Проверяет пустой RQL запрос
2. **handle()**:
   - Если DataStore поддерживает `multiCreate`, использует его
   - Иначе создает записи по одной с задержкой 10ms
   - Возврат массива ID созданных записей со статусом 201

```php
<?php
declare(strict_types=1);

namespace rollun\datastore\Middleware\Handler;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\Interfaces\DataStoreInterface;

class MultiCreateHandler extends AbstractHandler
{
    public function canHandle(ServerRequestInterface $request): bool
    {
        if ($request->getMethod() !== "POST") {
            return false;
        }
        
        $rows = $request->getParsedBody();
        
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
                    usleep(10000); // 10ms
                } catch (DataStoreException) {
                    // Игнорирование ошибок
                }
            }
            $result = array_column($result, $this->dataStore->getIdentifier());
        }
        
        return new JsonResponse(
            $result,
            201,
            ['Location' => $request->getUri()->getPath()]
        );
    }
}
```

## 6. CreateHandler - создание одной записи

**Класс**: `rollun\datastore\Middleware\Handler\CreateHandler`
**Файл**: `src/DataStore/src/Middleware/Handler/CreateHandler.php`

### Алгоритм выполнения:
1. **canHandle()**: 
   - Проверяет метод === "POST" и валидность body
   - Проверяет пустой RQL запрос
2. **handle()**:
   - Получает режим перезаписи из заголовка `If-Match`
   - Определяет primary key из body или атрибутов
   - Проверяет существование записи с таким ID
   - Если запись существует и нет режима перезаписи - исключение
   - Создание записи через `$this->dataStore->create($row, $overwriteMode)`
   - Возврат созданной записи со статусом 201 и заголовком Location

```php
<?php
namespace rollun\datastore\Middleware\Handler;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use rollun\datastore\DataStore\DataStoreException;

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

## 7. UpdateHandler - обновление записи

**Класс**: `rollun\datastore\Middleware\Handler\UpdateHandler`
**Файл**: `src/DataStore/src/Middleware/Handler/UpdateHandler.php`

### Алгоритм выполнения:
1. **canHandle()**: 
   - Проверяет метод === "PUT"
   - Определяет primary key из URL или body
   - Проверяет валидность body (ассоциативный массив)
   - Проверяет пустой RQL запрос
2. **handle()**:
   - Получает режим перезаписи
   - Проверяет существование записи
   - Обновление записи через `$this->dataStore->update($item, $overwriteMode)`
   - Если режим перезаписи и запись не существовала - статус 201

```php
<?php
namespace rollun\datastore\Middleware\Handler;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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

## 8. RefreshHandler - обновление DataStore

**Класс**: `rollun\datastore\Middleware\Handler\RefreshHandler`
**Файл**: `src/DataStore/src/Middleware/Handler/RefreshHandler.php`

### Алгоритм выполнения:
1. **canHandle()**: Проверяет метод === "PATCH" и пустой RQL запрос
2. **handle()**:
   - Проверяет что DataStore реализует `RefreshableInterface`
   - Вызывает `$this->dataStore->refresh()`
   - Возвращает пустой JSON ответ

```php
<?php
namespace rollun\datastore\Middleware\Handler;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use rollun\datastore\DataStore\Interfaces\RefreshableInterface;
use rollun\datastore\Middleware\RestException;

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

## 9. DeleteHandler - удаление записи

**Класс**: `rollun\datastore\Middleware\Handler\DeleteHandler`
**Файл**: `src/DataStore/src/Middleware/Handler/DeleteHandler.php`

### Алгоритм выполнения:
1. **canHandle()**: 
   - Проверяет метод === "DELETE" и наличие primaryKeyValue
   - Проверяет пустой RQL запрос
2. **handle()**:
   - Удаление записи через `$this->dataStore->delete($primaryKeyValue)`
   - Если запись не найдена - статус 204, иначе - возврат удаленной записи

```php
<?php
namespace rollun\datastore\Middleware\Handler;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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

## 10. QueriedUpdateHandler - обновление по RQL запросу

**Класс**: `rollun\datastore\Middleware\Handler\QueriedUpdateHandler`
**Файл**: `src/DataStore/src/Middleware/Handler/QueriedUpdateHandler.php`

### Алгоритм выполнения:
1. **canHandle()**: 
   - Проверяет метод === "PATCH" и отсутствие primaryKeyValue
   - Проверяет наличие RQL запроса с фильтром и лимитом
   - Проверяет что body - ассоциативный массив с полями для обновления
   - Проверяет отсутствие GROUP BY и SELECT в RQL
2. **handle()**:
   - Если DataStore поддерживает `queriedUpdate`, использует его
   - Иначе выполняет запрос, получает записи и обновляет по одной
   - Возврат массива ID обновленных записей

```php
<?php
namespace rollun\datastore\Middleware\Handler;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\Interfaces\DataStoreInterface;
use Xiag\Rql\Parser\Query;
use Psr\Http\Message\ResponseInterface;

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

## 11. ErrorHandler - обработка ошибок

**Класс**: `rollun\datastore\Middleware\Handler\ErrorHandler`
**Файл**: `src/DataStore/src/Middleware/Handler/ErrorHandler.php`

### Алгоритм выполнения:
1. **process()**: 
   - Вызывается если ни один Handler не смог обработать запрос
   - Выбрасывает `RestException` с детальной информацией о запросе
   - Исключение обрабатывается в `DataStoreApi` и возвращается как HTTP ошибка

```php
<?php
namespace rollun\datastore\Middleware\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use rollun\datastore\Middleware\RestException;

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

## RestException - исключение для REST

**Класс**: `rollun\datastore\Middleware\RestException`
**Файл**: `src/DataStore/src/Middleware/RestException.php`

```php
<?php
namespace rollun\datastore\Middleware;

class RestException extends \RuntimeException {}
```

## Заключение

Все Handler'ы наследуются от `AbstractHandler` и реализуют паттерн Chain of Responsibility:

1. **canHandle()** - проверяет возможность обработки запроса
2. **handle()** - выполняет обработку запроса
3. **process()** - делегирует обработку следующему Handler'у если текущий не может обработать

Порядок Handler'ов в `DataStoreRest` важен - они проверяются последовательно до первого подходящего.





