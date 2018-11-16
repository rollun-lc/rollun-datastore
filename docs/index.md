
# rollun-datastore

### Quick start

`rollun-datastore` - это библиотека, которая предоставляет единый интерфейс взаимодействие с любым хранилищем данных
на основе [Resource Query Language (RQL)](https://www.sitepen.com/blog/2010/11/02/resource-query-language-a-query-language-for-the-web-nosql/).
Существующие реализации: DbTable (для таблицы бд), CsvBase (для csv файлов), HttpClient (для внешнего ресурса через 
http), Memory (для [RAM](https://en.wikipedia.org/wiki/Random-access_memory)).

**Интерфейс `DataStoreInterface` определяет следующие основные методы для работы с абстрактным хранилищем:**

- `getIdentifier()` - возвращает имя поля, которое служит `primary key` для идентификации уникальной записи (по 
умолчанию это `id`);
- `count()` - возвращает количество записей;
- `create($itemData)` - создает новую запись (если такая запись уже существует или не указан `primary key`,
будет выброшен exception), возвращает созданную запись;
- `update($itemData)` - обновляет существующею запись (если такая запись не существует или не указан `primary key`,
будет выброшен exception), возвращает обновленную запись;
- `delete($id)` - удаляет запись по `primary key`, возвращает удаленную запись;
- `has($id)` - проверяет существует ли запись в хранилище, возвращает true/false;
- `read($id)` - возвращает запись по `primary key`;
- `query(Query $query)` - возвращает массив записей которые совпадают, указаному в `$query`, rql выражению.


Как уже было подмечено, для точной идентификации записи в хранилище используется `primary key`. Предполагается что
идентификатор не автоинкрементный, по этому вызов метода `create` без указание идентификатора выбросит exception
(или в случае старых версии приведет к ошибке уровня `E_USER_DEPRECATED`).

**Реализации `DataStoreInterface`, которые предоставляет библиотека:**

- `DbTable` - для таблиц баз данных (по скольку зависимость `TableGateway` предоставляется
[zendframework/zend-db](https://github.com/zendframework/zend-db), то есть возможность использовать MySQL,
PostgreSQL,Oracle, IBM DB2, Microsoft Sql Server, PDO и тд.);
- `SerializedDbTable` - тот же `DbTable`, только умеющий сериализоваться;
- `CsvBase` - для [CSV](https://en.wikipedia.org/wiki/Comma-separated_values) файлов;
- `HttpClient` - для внешних ресурсов через `http` (разумеется если этот ресурс умеет обрабатывать соответственные 
обращения);
- `Memory` - хранилище в оперативной памяти
- `Cacheable` - декоратор вокруг `DataStoresInterface`, который представляет возможность кэширования данных.

##### 1. DbTable и SerializedDbTable

Для того чтобы начать использовать DbTable data store нужен
[Zend\Db\TableGateway\TableGateway](https://zendframework.github.io/zend-db/table-gateway/)

Пример:

```php
<?php 

use Zend\Db\Adapter\Adapter;
use Zend\Db\TableGateway\TableGateway;
use rollun\datastore\DataStore\DbTable;

$dbConfig = [
    'driver'   => 'Mysqli',
    'database' => 'zend_db_example',
    'username' => 'developer',
    'password' => 'developer-password',
];

$adapter = new Adapter($dbConfig);
$tableGateway = new TableGateway('someTable', $adapter);

$dbTable = new DbTable($tableGateway);
$dbTable->create([
    'id' => 1,
    'name' => 'foo'
]);

var_dump($dbTable->read(1)); // ['id' => '1', 'name' => 'foo']
```

##### 2. CsvBase

Для работы з CsvBase нужно указать путь к существующему файлу (или имя файла, который находиться в временной системной
папке), разделитель и [LockHandler](https://symfony.com/doc/3.3/components/filesystem/lock_handler.html).

Пример:

```php
<?php 

use rollun\datastore\DataStore\CsvBase;
use Symfony\Component\Filesystem\LockHandler;

$filename = tempnam(sys_get_temp_dir(), 'csv');
$lockHandler = new LockHandler($filename);

$csvBase = new CsvBase($filename, ',', $lockHandler);
$csvBase->create([
    'id' => 1,
    'name' => 'foo'
]);

var_dump($csvBase->read(1)); // ['id' => '1', 'name' => 'foo']
```

##### 3. HttpClient

Для работы с HttpClient нужен [Zend\Http\Client](https://framework.zend.com/manual/2.4/en/modules/zend.http.client.html)
и URL.

```php
<?php 

use rollun\datastore\DataStore\HttpClient;
use Zend\Http\Client;

$client = new Client();
$url = 'http://example.com';

$httpClient = new HttpClient($client, $url);
$httpClient->create([
    'id' => 1,
    'name' => 'foo'
]);

var_dump($httpClient->read(1)); // ['id' => '1', 'name' => 'foo']
```

##### 4. Memory

Для работы с Memory нужно указать поля (если поля не будут указаны то будет выброшена ошибка уровня 
`E_USER_DEPRECATED`).

```php
<?php 

use rollun\datastore\DataStore\Memory;

$memory = new Memory(['id', 'name']);
$memory->create([
    'id' => 1,
    'name' => 'foo'
]);

var_dump($memory->read(1)); // ['id' => '1', 'name' => 'foo']
```

##### 5. Cacheable

`Cacheable` используется для того чтобы можно было хранить данные в кеше для более быстрого доступа и обновлять его.
Для этого `Cacheable` нужно источник данных, который реализует кдинственный метод `getAll()` интерфейса
`DataSourceInterface` и 
data store
Так же если источник данных поддерживает методы `DataStoresInterface` для записи данных, можно обновлять его.

Пример:

```php
<?php 

use rollun\datastore\DataStore\Memory;
use rollun\datastore\DataStore\Interfaces\DataSourceInterface;
use rollun\datastore\DataStore\Cacheable;

$data = [
    ['id' => 1, 'name' => 'foo1'],
    ['id' => 2, 'name' => 'foo2'],
    ['id' => 3, 'name' => 'foo3'],
    ['id' => 4, 'name' => 'foo4'],
];

$dataStore = new Memory(['id', 'name']);
$dataSource = new class($data) implements DataSourceInterface
{
    /** @var array */
    protected $data;
    
    public function __construct($data) {
        $this->data = $data;
    }
    
    public function getAll(){
        return $this->data;
    }
};

$cacheable = new Cacheable($dataSource, $dataStore);

var_dump($cacheable->count()); // 0
var_dump($cacheable->read(1)); // null

$cacheable->refresh();

var_dump($cacheable->count()); // 4
var_dump($cacheable->read(1)); // ['id' => 1, 'name' => 'foo1']
var_dump($cacheable->read(4)); // ['id' => 4, 'name' => 'foo4']
```


### Data Type

Data store не имеет никакого представления о типах данных которые он хранит, поэтому типизацией и хранением данных о себе 
data store занимается аспект data store который поддерживает интерфейс SchemableInterface
и реализует метод getSchema этого интерфейса. Для описания типа и форматтера для каждого столбца используется схема.
Схема это массив, ключ которого это название поля а значение - массив, в котором храниться в качестве ключей `type`,
`formatter`, а в качестве значений соответствующие классы.

Пример:

```php
$schema = [
  'id' => [
      'type' => \Module\Type\TypeInt::class,
      'formatter' => Module\Formatter\StringFormatter::class,
  ],
  'name' => [
      'type' => \Module\Type\TypeString::class,
      'formatter' => Module\Formatter\StringFormatter::class,
  ],
]
``` 

Для введения типа используются объекты, которые реализуют интерфейс TypeInterface.

Существующие типы:
- TypeBoolean
- TypeChar
- TypeFloat
- TypeInt
- TypeString

Также для удобного использование типов предусмотрен [DTO](https://en.wikipedia.org/wiki/Data_transfer_object)
(Data transfer object). Такой DTO удобно передавать в качестве данных для методов `create`, `update`, `rewrite`.
DTO должен принимать и хранить в себе только объекты типа TypeInterface и возвращать уже сами значения.
DTO не должен изменяться, тоесть ни каких сетеров.
Конструктор на вход должен принимать все необходимые значение в виде TypeInterface.

Пример:

```php
<?php

namespace Module\Dto;

use rollun\datastore\DataStore\BaseDto;
use rollun\datastore\DataStore\Type\TypeInt;
use rollun\datastore\DataStore\Type\TypeString;

class UserDto extends BaseDto
{
    protected $id;

    protected $name;
    
    public function __construct(TypeInt $id, TypeString $name) {
        $this->id = $id;
        $this->name = $name;
    }
    
    public function getId()
    {
        $this->id->toTypeValue();
    }
    
    public function getName()
    {
        $this->name->toTypeValue();
    }
}

// Examples
$id = new TypeInt('1');
$name = new TypeString('name');

$user = new UserDto($id, $name);

// The same as 'new UserDto($id, $name)'
$user = UserDto::createFromArray([
    'id' => 1,
    'name' => 'foo'
]);

echo $user->getId(); // 1 (int)
echo $user->getName(); // 'name' (string)
```

Зачастую в data store нужно передать отформатированные каким то образом данные полученные из DTO. 
Для этого удобно использовать форматеры. Форматеры (formatter) реализуют интерфейс FormatterInterface.

Пример:

```php
<?php

namespace Module\Formatter;

use rollun\datastore\DataStore\Formatter\FormatterInterface;
use Module\Dto\UserDto;

class StringFormatter implements FormatterInterface
{
    public function format($value)
    {
        return (string)$value;
    }
}
```


### Middleware

Как было указано, для `HttpClient` нужно предоставить url, который будет корректно обрабатывать `GET`, `POST`, '`PUT`,
`DELETE`, `PATCH` в соответствии с RESTful API и уметь обрабатывать RQL. С этой задачей может справиться `Data Store Middleware`.
Для этого нужно в качестве middleware обработчика route указать `DataStoreApi`. Нужно чтобы route был типа 
`/api/datastore/{resourceName}[/{id}]` и иметь в сервис с именем `resourceName` в реализации `PSR ContainerInterface`
вашего приложения.

Пример конфигураций route для 
[zendframework/zend-expressive-skeleton](https://github.com/zendframework/zend-expressive-skeleton):

1. Посредством конфигурационного файла для [zendframework/zend-expressive](https://github.com/zendframework/zend-expressive)
```php
<?php

use rollun\datastore\Middleware\DataStoreApi;

return [
    'routes' => [
        [
            'name' => DataStoreApi::class,
            'path' => '/api/datastore/{resourceName}[/{id}]',
            'middleware' => DataStoreApi::class,
            'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'],
        ],
    ],
];
```

2. Через объект `\Zend\Expressive\Application`

```php
<?php

use rollun\datastore\Middleware\DataStoreApi;

/** @var \Zend\Expressive\Application $app */
$app->route(
    '/api/datastore[/{resourceName}[/{id}]]', // route pattern
    DataStoreApi::class, // middleware
    ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'],
    DataStoreApi::class // route name
);
```


### RQL

RQL - простой язык построения запросов для абстрактных хранилищ. В данной библиотеки реализация RQL от 
[xiag/rql-parser](https://github.com/xiag-ag/rql-parser). Построить запрос можна с помощью строки:

```php
<?php

use rollun\datastore\DataStore\Memory;
use rollun\datastore\Rql\RqlQuery;

$dataStore = new Memory(['id', 'name', 'age']);

// You can use string
$rql = new RqlQuery('and(ge(id,1),or(not(eqn(name)),not(eqn(surname))))&limit(1)&select(email,password)');
$dataStore->query($rql);
```

Так и с помощью `node` объектов:

```php
<?php

use rollun\datastore\DataStore\Memory;
use rollun\datastore\Rql\RqlQuery;
use Xiag\Rql\Parser\Node as XiagNode;
use rollun\datastore\Rql\Node as RollunNode;

$dataStore = new Memory(['id', 'name', 'age']);

$rql = new RqlQuery(
    new XiagNode\Query\LogicOperator\AndNode([
        new XiagNode\Query\ScalarOperator\GeNode('eq', 1),
        new XiagNode\Query\LogicOperator\OrNode([
            new XiagNode\Query\LogicOperator\NotNode([
                new RollunNode\BinaryNode\EqnNode('name')    
            ]),
            new XiagNode\Query\LogicOperator\NotNode([
                new RollunNode\BinaryNode\EqnNode('surname')    
            ])
        ])
    ])
);
$rql->setLimit(new XiagNode\LimitNode(1));
$rql->setSelect(new XiagNode\SelectNode(['email', 'password']));
$dataStore->query($rql);
```


### Table mysql manager

Так же библиотека предоставляет возможность создавать и удалять таблицы бд используя `TableMysqlManager`. Для это нужен
`Zend\Db\Adapter\Adapter`. Создать таблицы можно и при инициализации объекта и при вызове методы `createTable()` с 
помощью конфигураций. Структура конфигураций представляет собой массив, где ключ - имя поля, а значение - массив со 
следующими ключами:

1. `TableManagerMysql::FIELD_TYPE` указывает на тип поля. Доступные значения и их константы разбитые на групы:
    - `TableManagerMysql::COLUMN_SIMPLE`:
        - `TableManagerMysql::TYPE_BIG_INTEGER`
        - `TableManagerMysql::TYPE_BOOLEAN`
        - `TableManagerMysql::TYPE_DATE`
        - `TableManagerMysql::TYPE_DATETIME`
        - `TableManagerMysql::TYPE_INTEGER`
        - `TableManagerMysql::TYPE_TIME`
        - `TableManagerMysql::TYPE_TIMESTAMP`
        - `TableManagerMysql::TYPE_BINARY`
    - `TableManagerMysql::COLUMN_LENGTH`:
        - `TableManagerMysql::TYPE_BLOB`
        - `TableManagerMysql::TYPE_CHAR`
        - `TableManagerMysql::TYPE_TEXT`
        - `TableManagerMysql::TYPE_VARBINARY`
        - `TableManagerMysql::TYPE_VARCHAR`
    - `TableManagerMysql::COLUMN_PRECISION`:
        - `TableManagerMysql::TYPE_DECIMAL`
        - `TableManagerMysql::TYPE_FLOAT`
        - `TableManagerMysql::TYPE_FLOATING`
    
2. `TableManagerMysql::FIELD_PARAMS` указывает на дополнительные свойства полей в виде массива. Доступные свойства - константы:
    - Для всех групп:
        - `TableManagerMysql::PROPERTY_NULLABLE`
        - `TableManagerMysql::PROPERTY_DEFAULT`
        - `TableManagerMysql::PROPERTY_OPTIONS` ключ, значением которого сформирован с массива доступных опций:
            -  `TableManagerMysql::OPTION_AUTOINCREMENT`
            -  `TableManagerMysql::OPTION_UNSIGNED`
            -  `TableManagerMysql::OPTION_ZEROFILL`
            -  `TableManagerMysql::OPTION_IDENTITY`
            -  `TableManagerMysql::OPTION_SERIAL`
            -  `TableManagerMysql::OPTION_COMMENT`
            -  `TableManagerMysql::OPTION_COLUMNFORMAT`
            -  `TableManagerMysql::OPTION_FORMAT`
            -  `TableManagerMysql::OPTION_STORAGE`
    - `TableManagerMysql::COLUMN_LENGTH`:
        - `TableManagerMysql::PROPERTY_LENGTH`
    - `TableManagerMysql::COLUMN_PRECISION`:
        - `TableManagerMysql::PROPERTY_DIGITS`
        - `TableManagerMysql::PROPERTY_DECIMAL`
        
3. `TableManagerMysql::FOREIGN_KEY` описывает внешний ключ данного поля в виде массива из доступных констант:
    - `TableManagerMysql::OPTION_REFERENCE_TABLE`
    - `TableManagerMysql::OPTION_REFERENCE_COLUMN`
    - `TableManagerMysql::OPTION_ON_DELETE_RULE`
    - `TableManagerMysql::OPTION_ON_UPDATE_RULE`
    - `TableManagerMysql::OPTION_NAME`
        
4. `TableManagerMysql::UNIQUE_KEY` описывает уникальный ключ. В качестве значения принимается имя ключа.


Пример:

```php
<?php

use rollun\datastore\TableGateway\TableManagerMysql;
use Zend\Db\Adapter\Adapter;

$tableConfig = [
    'id' => [
        TableManagerMysql::FIELD_TYPE => TableManagerMysql::TYPE_INTEGER,
        TableManagerMysql::FIELD_PARAMS => [
            TableManagerMysql::PROPERTY_OPTIONS => [
                TableManagerMysql::OPTION_AUTOINCREMENT => true,
            ],
        ],
    ],
    'name' => [
        TableManagerMysql::FIELD_TYPE => TableManagerMysql::TYPE_VARCHAR,
        TableManagerMysql::FIELD_PARAMS => [
            TableManagerMysql::PROPERTY_LENGTH => 10,
            TableManagerMysql::PROPERTY_NULLABLE => true,
            TableManagerMysql::PROPERTY_DEFAULT => 'foo',
        ],
        TableManagerMysql::UNIQUE_KEY => true,
    ],
];

$dbConfig = [
    'driver'   => 'Mysqli',
    'database' => 'zend_db_example',
    'username' => 'developer',
    'password' => 'developer-password',
];

$adapter = new Adapter($dbConfig);
$tableManager = new TableManagerMysql($adapter);
$tableManager->createTable('tableName1', $tableConfig);
```

Для того чтобы создать таблицы и/или задать конфигурации для таблиц при инициализации объекта нужно задать 
конфигурации таблиц под ключами `TableManagerMysql::KEY_AUTOCREATE_TABLES` и `TableManagerMysql::KEY_TABLES_CONFIGS` 
соответственно вторым параметром конструктора.

Пример:

```php
<?php

use rollun\datastore\TableGateway\TableManagerMysql;
use Zend\Db\Adapter\Adapter;

$tablesConfigs = [
    TableManagerMysql::KEY_AUTOCREATE_TABLES => [
        'tableName2' => [
            'id' => [
                TableManagerMysql::FIELD_TYPE => TableManagerMysql::TYPE_INTEGER,
                TableManagerMysql::FIELD_PARAMS => [
                    TableManagerMysql::PROPERTY_OPTIONS => [
                        TableManagerMysql::OPTION_AUTOINCREMENT => true,
                    ],
                ],
                TableManagerMysql::FOREIGN_KEY => [
                    TableManagerMysql::OPTION_REFERENCE_TABLE => 'tableName1',
                    TableManagerMysql::OPTION_REFERENCE_COLUMN => 'id',
                    TableManagerMysql::OPTION_ON_DELETE_RULE => 'cascade',
                    TableManagerMysql::OPTION_ON_UPDATE_RULE => null,
                    TableManagerMysql::OPTION_NAME => null,
                ],
            ],
            'name' => [
                TableManagerMysql::FIELD_TYPE => TableManagerMysql::TYPE_VARCHAR,
                TableManagerMysql::FIELD_PARAMS => [
                    TableManagerMysql::PROPERTY_LENGTH => 10,
                    TableManagerMysql::PROPERTY_NULLABLE => true,
                    TableManagerMysql::PROPERTY_DEFAULT => 'foo',
                ],
            ],
        ],
    ],
    TableManagerMysql::KEY_TABLES_CONFIGS => [
        'tableName3' => [
            'id' => [
                TableManagerMysql::FIELD_TYPE => TableManagerMysql::TYPE_VARCHAR,
                TableManagerMysql::FIELD_PARAMS => [
                    TableManagerMysql::PROPERTY_LENGTH => 10,
                    TableManagerMysql::PROPERTY_NULLABLE => true,
                    TableManagerMysql::PROPERTY_DEFAULT => 'foo',
                ],
            ],
        ],
    ],
];

$dbConfig = [
    'driver'   => 'Mysqli',
    'database' => 'zend_db_example',
    'username' => 'developer',
    'password' => 'developer-password',
];

$adapter = new Adapter($dbConfig);
$tableManager = new TableManagerMysql($adapter, $tablesConfigs);
```


### Cleaner 

