# Ультра-детальный анализ HTTP Pipeline

## Полный путь выполнения запроса

### 1. Точка входа - public/test.php

**Файл**: `public/test.php`
**Namespace**: глобальный

```php
<?php
use rollun\datastore\Middleware\DataStoreApi;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Laminas\HttpHandlerRunner\RequestHandlerRunner;
use Laminas\Stratigility\Middleware\ErrorResponseGenerator;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Laminas\Stratigility\MiddlewarePipe;

error_reporting(E_ALL ^ E_USER_DEPRECATED);

// Проверка статических файлов
if (php_sapi_name() === 'cli-server'
    && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

chdir(dirname(__DIR__));
require 'vendor/autoload.php';

// Создание HTTP запроса из глобальных переменных
$request = ServerRequestFactory::fromGlobals(
    $_SERVER,
    $_GET,
    $_POST,
    $_COOKIE,
    $_FILES
);

// Получение DI контейнера
$container = require 'config/container.php';
\rollun\dic\InsideConstruct::setContainer($container);

// Настройка фабрики запросов
$serverRequestFactory = [ServerRequestFactory::class, 'fromGlobals'];

// Настройка генератора ошибок
$errorResponseGenerator = function (Throwable $e) {
    $generator = new ErrorResponseGenerator();
    return $generator($e, new ServerRequest(), new Response());
};

// Создание middleware pipe
$middlewarePipe = new MiddlewarePipe();
$middlewarePipe->pipe($container->get(DataStoreApi::class));

// Запуск обработки запроса
$runner = new RequestHandlerRunner(
    $middlewarePipe,
    new SapiEmitter(),
    $serverRequestFactory,
    $errorResponseGenerator
);
$runner->run();
```

### 2. Конфигурация контейнера - config/container.php

**Файл**: `config/container.php`
**Namespace**: глобальный

```php
<?php
use Laminas\ServiceManager\Config;
use Laminas\ServiceManager\ServiceManager;

// Загрузка конфигурации
$config = require __DIR__ . '/config.php';

// Создание контейнера
$container = new ServiceManager();
(new Config($config['dependencies']))->configureServiceManager($container);

// Инъекция конфигурации
$container->setService('config', $config);

return $container;
```

### 3. Конфигурация приложения - config/config.php

**Файл**: `config/config.php`
**Namespace**: глобальный

```php
<?php
use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\ConfigAggregator\PhpFileProvider;
use Symfony\Component\Dotenv\Dotenv;

// Загрузка переменных окружения
if (is_file('.env')) {
    (new Dotenv())->usePutenv(true)->load('.env');
}

// Определение окружения
$appEnv = getenv('APP_ENV');

// Агрегация конфигурации
$aggregator = new ConfigAggregator([
    \Mezzio\ConfigProvider::class,
    \Mezzio\Router\ConfigProvider::class,
    \Laminas\Cache\ConfigProvider::class,
    \Laminas\Mail\ConfigProvider::class,
    \Laminas\Db\ConfigProvider::class,
    \Laminas\Validator\ConfigProvider::class,
    \Laminas\HttpHandlerRunner\ConfigProvider::class,
    
    // Rollun конфигурация
    \rollun\uploader\ConfigProvider::class,
    \rollun\datastore\ConfigProvider::class,
    \rollun\repository\ConfigProvider::class,
    \rollun\logger\ConfigProvider::class,
    
    // Загрузка конфигурационных файлов
    new PhpFileProvider('config/autoload/{{,*.}global,{,*.}local}.php'),
    new PhpFileProvider(realpath(__DIR__) . "/autoload/{{,*.}global.{$appEnv},{,*.}local.{$appEnv}}.php"),
]);

return $aggregator->getMergedConfig();
```

### 4. DataStore ConfigProvider

**Класс**: `rollun\datastore\ConfigProvider`
**Файл**: `src/DataStore/src/ConfigProvider.php`

```php
<?php
namespace rollun\datastore;

use rollun\datastore\DataStore\Aspect\Factory\AspectAbstractFactory;
use rollun\datastore\DataStore\Aspect\Factory\AspectSchemaAbstractFactory;
use rollun\datastore\DataStore\ConditionBuilder\SqlConditionBuilderAbstractFactory;
use rollun\datastore\DataStore\DataStorePluginManager;
use rollun\datastore\DataStore\DataStorePluginManagerFactory;
use rollun\datastore\DataStore\Factory\CacheableAbstractFactory;
use rollun\datastore\DataStore\Factory\CsvAbstractFactory;
use rollun\datastore\DataStore\Factory\DbTableAbstractFactory;
use rollun\datastore\DataStore\Factory\HttpClientAbstractFactory;
use rollun\datastore\DataStore\Factory\MemoryAbstractFactory;
use rollun\datastore\DataStore\Schema\ArraySchemaRepository;
use rollun\datastore\DataStore\Schema\ArraySchemaRepositoryFactory;
use rollun\datastore\DataStore\Schema\SchemaApiRequestHandler;
use rollun\datastore\DataStore\Schema\SchemaApiRequestHandlerFactory;
use rollun\datastore\DataStore\Schema\SchemasRepositoryInterface;
use rollun\datastore\DataStore\Scheme\Factory\SchemeAbstractFactory;
use rollun\datastore\Middleware\DataStoreApi;
use rollun\datastore\Middleware\Determinator;
use rollun\datastore\Middleware\Factory\DataStoreApiFactory;
use rollun\datastore\Middleware\Factory\DeterminatorFactory;
use rollun\datastore\Middleware\RequestDecoder;
use rollun\datastore\Middleware\ResourceResolver;
use rollun\datastore\TableGateway\Factory\SqlQueryBuilderAbstractFactory;
use rollun\datastore\TableGateway\Factory\TableGatewayAbstractFactory;
use rollun\datastore\TableGateway\Factory\TableManagerMysqlFactory;
use rollun\datastore\TableGateway\TableManagerMysql;
use Laminas\ServiceManager\Factory\InvokableFactory;

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
            'aliases' => [
                SchemasRepositoryInterface::class => ArraySchemaRepository::class,
            ],
            'factories' => [
                ResourceResolver::class => InvokableFactory::class,
                RequestDecoder::class => InvokableFactory::class,
                Determinator::class => DeterminatorFactory::class,
                DataStoreApi::class => DataStoreApiFactory::class,
                DataStorePluginManager::class => DataStorePluginManagerFactory::class,
                'TableManagerMysql' => TableManagerMysqlFactory::class,
                TableManagerMysql::class => TableManagerMysqlFactory::class,
                SchemaApiRequestHandler::class => SchemaApiRequestHandlerFactory::class,
                ArraySchemaRepository::class => ArraySchemaRepositoryFactory::class,
            ],
            'abstract_factories' => [
                // Data stores
                CacheableAbstractFactory::class,
                CsvAbstractFactory::class,
                DbTableAbstractFactory::class,
                HttpClientAbstractFactory::class,
                MemoryAbstractFactory::class,
                // Aspects
                AspectAbstractFactory::class,
                AspectSchemaAbstractFactory::class,
                // Scheme
                SchemeAbstractFactory::class,
                TableGatewayAbstractFactory::class,
                SqlConditionBuilderAbstractFactory::class,
                SqlQueryBuilderAbstractFactory::class,
            ],
        ];
    }
}
```

### 5. DataStoreApiFactory

**Класс**: `rollun\datastore\Middleware\Factory\DataStoreApiFactory`
**Файл**: `src/DataStore/src/Middleware/Factory/DataStoreApiFactory.php`

```php
<?php
namespace rollun\datastore\Middleware\Factory;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use rollun\datastore\Middleware\DataStoreApi;
use rollun\datastore\Middleware\Determinator;
use Laminas\ServiceManager\Factory\FactoryInterface;

class DataStoreApiFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $dataStoreDeterminator = $container->get(Determinator::class);
        $logger = $container->get(LoggerInterface::class);
        
        return new DataStoreApi($dataStoreDeterminator, null, $logger);
    }
}
```

### 6. DataStoreApi Middleware

**Класс**: `rollun\datastore\Middleware\DataStoreApi`
**Файл**: `src/DataStore/src/Middleware/DataStoreApi.php`

```php
<?php
namespace rollun\datastore\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\TextResponse;
use Laminas\Stratigility\Middleware\RequestHandlerMiddleware;
use Laminas\Stratigility\MiddlewarePipe;

class DataStoreApi implements MiddlewareInterface
{
    protected $middlewarePipe;
    protected $logger;

    public function __construct(
        Determinator $determinator,
        RequestHandlerInterface $renderer = null,
        LoggerInterface $logger = null
    ) {
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
            if ($this->logger) {
                $this->logger->error("Exception in Datastore middleware", [
                    'exception' => $e,
                ]);
            }
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

### 7. ResourceResolver Middleware

**Класс**: `rollun\datastore\Middleware\ResourceResolver`
**Файл**: `src/DataStore/src/Middleware/ResourceResolver.php`

```php
<?php
namespace rollun\datastore\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

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

### 8. RequestDecoder Middleware

**Класс**: `rollun\datastore\Middleware\RequestDecoder`
**Файл**: `src/DataStore/src/Middleware/RequestDecoder.php`

```php
<?php
namespace rollun\datastore\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use rollun\utils\Json\Serializer;
use rollun\datastore\Rql\RqlParser;

class RequestDecoder implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
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

### 9. DeterminatorFactory

**Класс**: `rollun\datastore\Middleware\Factory\DeterminatorFactory`
**Файл**: `src/DataStore/src/Middleware/Factory/DeterminatorFactory.php`

```php
<?php
namespace rollun\datastore\Middleware\Factory;

use Psr\Container\ContainerInterface;
use rollun\datastore\DataStore\DataStorePluginManager;
use rollun\datastore\Middleware\Determinator;
use Laminas\ServiceManager\Factory\FactoryInterface;

class DeterminatorFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $dataStorePluginManager = $container->get(DataStorePluginManager::class);
        return new Determinator($dataStorePluginManager);
    }
}
```

### 10. Determinator Middleware

**Класс**: `rollun\datastore\Middleware\Determinator`
**Файл**: `src/DataStore/src/Middleware/Determinator.php`

```php
<?php
namespace rollun\datastore\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use rollun\datastore\DataStore\Aspect\AspectTyped;
use rollun\datastore\DataStore\DataStorePluginManager;
use Laminas\Diactoros\Response\EmptyResponse;

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

### 11. DataStorePluginManager

**Класс**: `rollun\datastore\DataStore\DataStorePluginManager`
**Файл**: `src/DataStore/src/DataStore/DataStorePluginManager.php`

```php
<?php
namespace rollun\datastore\DataStore;

use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use Laminas\ServiceManager\AbstractPluginManager;

class DataStorePluginManager extends AbstractPluginManager
{
    protected $instanceOf = DataStoresInterface::class;
}
```

### 12. DataStorePluginManagerFactory

**Класс**: `rollun\datastore\DataStore\DataStorePluginManagerFactory`
**Файл**: `src/DataStore/src/DataStore/DataStorePluginManagerFactory.php`

```php
<?php
namespace rollun\datastore\DataStore;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class DataStorePluginManagerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $middlewarePluginManager = new DataStorePluginManager($container);
        $config = $container->get("config");
        $middlewarePluginManager->configure($config["dependencies"]);
        
        return $middlewarePluginManager;
    }
}
```

### 13. DataStoreRest Middleware

**Класс**: `rollun\datastore\Middleware\DataStoreRest`
**Файл**: `src/DataStore/src/Middleware/DataStoreRest.php`

```php
<?php
namespace rollun\datastore\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use rollun\datastore\Middleware\Handler;
use Laminas\Stratigility\MiddlewarePipe;

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

## Заключение

HTTP Pipeline в rollun-datastore состоит из следующих этапов:

1. **Точка входа** - создание HTTP запроса и DI контейнера
2. **DataStoreApi** - основной middleware с обработкой ошибок
3. **ResourceResolver** - извлечение ресурса и ID из URL
4. **RequestDecoder** - парсинг RQL, JSON, заголовков
5. **Determinator** - выбор DataStore по имени ресурса
6. **DataStoreRest** - выбор подходящего Handler'а
7. **Handler** - выполнение конкретной операции

Каждый этап обрабатывает определенную часть запроса и передает управление следующему компоненту в цепочке.





