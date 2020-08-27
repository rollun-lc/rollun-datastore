
# rollun-datastore

### Установка

Установить с помощью [composer](https://getcomposer.org/).
```bash
composer require rollun-com/rollun-datastore
```

Чтобы начать пользовать библиотекой, нужно подключить следующие `ConfigProvider` в файл конфигурации для
[ServiceManager](https://github.com/zendframework/zend-servicemanager):
- `rollun\datastore\ConfigProvider`
- `rollun\uploader\ConfigProvider`


##### Тестирование
Чтобы запустить тесты нужно:
1. установить обязательные переменные указанные в `.env.dist`, установить файлы конфигурации 
(`rollun\datastore\AssetInstaller`) для тестового окружения и подключить `ConfigProvider` в 
конфигурационный файл.
2. Изменить название файла конфига в дирректории autoload.

```bash
composer lib install
```

и запустить встроенный `php` сервер
```bash
php -S localhost:9000 -t public public/test.php
```

### Getting Started

`rollun-datastore` - это библиотека, которая предоставляет единый интерфейс взаимодействие с любым хранилищем данных
на основе [Resource Query Language (RQL)](https://www.sitepen.com/blog/2010/11/02/resource-query-language-a-query-language-for-the-web-nosql/).
Существующие реализации: DbTable (для таблицы бд), CsvBase (для csv файлов), HttpClient (для внешнего ресурса через 
http), Memory (для [RAM](https://en.wikipedia.org/wiki/Random-access_memory)).

**Интерфейс `DataStoresInterface` определяет следующие основные методы для работы с абстрактным хранилищем**
(Так же `DataStoresInterface` интерфейс расширяет интерфейсы `IteratorAggregate` и `Countable`):

- `getIdentifier()` - возвращает имя поля, которое служит `identifier`(идентификатором) уникальной записи (по 
умолчанию это `id`);
- `create($itemData, $rewriteIfExist = false)` - создает новую запись (если `identifier`, будет выброшен exception),
 возвращает созданную запись. Если запись существует и указан `$rewriteIfExist = true`, запись будет пересоздана, в 
 противном случае будет выброшено исключение. Возвращает созданную запись;
- `update($itemData)` - обновляет существующею запись (если такая запись не существует или не указан `identifier`,
будет выброшен exception), возвращает обновленную запись;
- `delete($id)` - удаляет запись по `identifier`, возвращает удаленную запись;
- `deleteAll()` - удаляет все запись, список идентификаторов удаленных записей;
- `has($id)` - проверяет существует ли запись в хранилище, возвращает `true`/`false`;
- `read($id)` - возвращает запись по `identifier`;
- `query(Query $query)` - возвращает массив записей которые совпадают, указаному в `$query`, **RQL** выражении.

При переходе на версию `rollun/rollun-datastore 6` интерфейс хранилище измениться на `DataStoreInterface` 
(Вам не показалось, интерфейс имеет тоже название только без `s` окончания). Метод `deleteAll` будет удален и будут
добавлено несколько новых методов (Все выше перечисленные методы уже реализованы в `DataStoreAbstract` и `DbTable`):
- `multiCreate($records)` - создает несколько новых записей (если запись уже существует она не будет создаваться). 
Возвращает список идентификаторов успешно созданных записей;
- `multiUpdate($records)` - обновляет несколько существующих записей (если запись не существует она не будет 
обновляться). Возвращает список идентификаторов успешно обновленных записей;
- `rewrite($record)` - перезаписывает запись (создает запись, если она не существует или удаляет и создает запись 
если не существует). Возвращает перезаписанную запись;
- `multiRewrite($records)` - перезаписывает несколько существующих записей. Возвращает список идентификаторов успешно
обновленных записей;
- `queriedUpdate($record, $query Query)` - обновляет записи в соответствии с **RQL** запросом. Возвращает список 
идентификаторов обновленных записей;
- `queriedDelete($query Query)` - удаляет записи в соответствии с **RQL** запросом. Возвращает список 
идентификаторов удаленных записей.

Так же в новом интерфейсе скорее всего появиться (но еще не утвержден) новый метод `getNext($id)`, 
который возвращает следующую запись после записи с идентификатором `$id`. Если передать `$id = null` будет возвращена 
первая запись, а если будет возвращен `null` значит запись с `$id` - последняя. Если запись с `$id` не найдено будет 
выброшено исключение.

Как уже было подмечено, для точной идентификации записи в хранилище используется `identifier`. Предполагается что
идентификатор не автоинкрементный, по этому вызов метода `create` и `update` без указание идентификатора выбросит 
exception (или в случае старых версии приведет к ошибке уровня `E_USER_DEPRECATED`).

**Реализации `DataStoresInterface`, которые предоставляет библиотека:**

- `DbTable` - для таблиц баз данных (по скольку зависимость `TableGateway` предоставляется
[zendframework/zend-db](https://github.com/zendframework/zend-db), то есть возможность использовать MySQL,
PostgreSQL,Oracle, IBM DB2, Microsoft Sql Server, PDO и тд.);
- `SerializedDbTable` - тот же `DbTable`, только умеющий сериализоваться;
- `CsvBase` - для [CSV](https://en.wikipedia.org/wiki/Comma-separated_values) файлов;
- `HttpClient` - для внешних ресурсов через `http` (разумеется если этот ресурс умеет обрабатывать соответственные 
обращения);
- `Memory` - хранилище в оперативной памяти;
- `Cacheable` - декоратор вокруг `DataStoresInterface`, который представляет возможность кэширования данных.

##### 1. `DbTable` и `SerializedDbTable`

Для того чтобы начать использовать DbTable хранилище нужен
[Zend\Db\TableGateway\TableGateway](https://zendframework.github.io/zend-db/table-gateway/).

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

##### 2. `CsvBase`

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

##### 3. `HttpClient`

Для работы с HttpClient нужен [Zend\Http\Client](https://framework.zend.com/manual/2.4/en/modules/zend.http.client.html)
и URL.

```php
<?php 

use rollun\datastore\DataStore\HttpClient;
use Zend\Http\Client;

$client = new Client();
$url = 'http://example.com';

$httpClient = new HttpClient($client, $url);
$httpClient->multiCreate(
    [
        ['id' => 1, 'name' => 'name 1'],
        ['id' => 2, 'name' => 'name 2']
    ]
);

var_dump($httpClient->read(1)); // ['id' => '1', 'name' => 'name 1']
```

##### 4. `Memory`

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

##### 5. `Cacheable`

`Cacheable` используется для того чтобы можно было хранить данные в кеше для более быстрого доступа и обновлять его.
Для этого `Cacheable` нужно источник данных, который реализует единственный метод `getAll()` интерфейса
`DataSourceInterface`. Так же если источник данных поддерживает методы `DataStoresInterface` для записи данных, можно 
обновлять его.

Пример:

```php
<?php 

use rollun\datastore\DataStore\Memory;
use rollun\datastore\DataSource\DataSourceInterface;
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
    /** @var \Traversable */
    protected $data;
    
    public function __construct($data) {
        $this->data = $data;
    }
    
    public function getAll(): \Traversable {
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

Хранилище не имеет никакого представления о типах данных которые он хранит, поэтому типизацией и хранением данных о 
себе занимается аспект вокруг `DataStoresInterface`, который поддерживает интерфейс `SchemableInterface`
и реализует метод `getSchema` этого интерфейса. Для описания типа и форматтера для каждого столбца используется схема.
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

Для введения типа используются объекты, которые реализуют интерфейс `TypeInterface`.

**Существующие типы:**
- `TypeBoolean`
- `TypeChar`
- `TypeFloat`
- `TypeInt`
- `TypeString`

Также для удобного использование типов предусмотрен [DTO](https://en.wikipedia.org/wiki/Data_transfer_object)
(Data transfer object). Такой DTO удобно передавать в качестве данных для методов `create`, `update`.
DTO должен принимать и хранить в себе только объекты типа `TypeInterface` и возвращать уже сами значения.
DTO не должен изменяться (тоесть ни каких сетеров).
Конструктор на вход должен принимать все необходимые значение в виде `TypeInterface`.

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

Зачастую в хранилище нужно передать отформатированные каким то образом данные полученные из DTO. 
Для этого удобно использовать форматеры. Форматеры (formatter) реализуют интерфейс `FormatterInterface`.

**Существующие форматтеры:**
- `BooleanFormatter`
- `CharFormatter`
- `FloatFormatter`
- `IntFormatter`
- `StringFormatter`

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
    '/api/datastore/{resourceName}[/{id}]', // route pattern
    DataStoreApi::class, // middleware
    ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'],
    DataStoreApi::class // route name
);
```

#### Headers

**Заголовки запроса**
- `If-Match` - заголовок который указывает на то что запись должна быть создана если не существует при обновлении
и перезаписана если существует при создании записи для методов `update` и `create` соответственно
(второй параметр для указанных методов: `$createIfAbsent` и `$rewriteIfExist` - соответственно). Если `If-Match: *` тогда
параметры `$createIfAbsent` и `$rewriteIfExist` будут `true`, а если указано другое значение заголовка
или заголовка вообще не существует эти парметры будут иметь значение `false`.
- `With-Content-Range` - заголовок который указывает на то будет ли возвращен ответ с заголовком `Content-Range`.
    > Из внутренностей: для того чтобы создать ответ с заголовком `Content-Range` нужно вызвать метод `count` в `datastore`,
    поэтому для `datastore` с генерируемыми данными могут возникнут проблемы.

**Заголовки запроса**
- `Datastore-Scheme` - заголовок в котором указан `json` закодирована схема `Datastore`, если он обернут в `AspectType`.
- `X_MULTI_CREATE` - заголовок, который свидетельствует поддержки multiCreate 
- `X_DATASTORE_IDENTIFIER` - заголовок в котором указан название ID колонки
- `Download` - заголовок указывает на то, что мы хотим скачать файл. В значение следует указать тип файла. Например csv.

Псевдокод для скачивания данный по клику на кнопку:
```javascript
    $('#GetFile').on('click', function () {
        $.ajax({
            beforeSend: function (jqXHR, settings) {
                jqXHR.setRequestHeader('Download', 'csv');
            },
            url: 'http://rollun.local/api/datastore/dataStore1',
            method: 'GET',
            xhrFields: {
                responseType: 'blob'
            },
            success: function (data) {
                var a = document.createElement('a');
                var url = window.URL.createObjectURL(data);
                a.href = url;
                a.download = 'test.csv';
                document.body.append(a);
                a.click();
                a.remove();
                window.URL.revokeObjectURL(url);
            }
        });
    });
```  

### RQL

RQL - простой язык построения запросов для абстрактных хранилищ. В данной библиотеки реализация RQL от 
[xiag/rql-parser](https://github.com/xiag-ag/rql-parser). Атомарной единицей запроса является нода. Есть несколько вид 
нод: операторов (составляют сравнений), групировки, выборки, сортировки и нода лимита. 
Все эти типы нод в строковом представлении разделяются знаком `&`. Построить запрос можно с помощью строки или с 
помощю объектов.

**Существующие ноды объекты и их строковые эквиваленты:**

1. Операторы для работы с массивами.
    * `in`. Оператор который позволяет определить, совпадает ли значение поля со значением в списке.
        - Объект: `Xiag\Rql\Parser\Node\Query\ArrayOperator\InNode`. Пример: 
             ```php
             $query = new InNode('name', ['John', 'Jackson', 'Liam']);
             ```
        - Строковое представление: `in`. Пример:
            ```php
            $query = 'in(name,John,Jackson,Liam)';
            ```
    * `out`. Оператор который позволяет определить, НЕ совпадает ли значение поля со значением в списке (обратное к `in`).
        - Объект: `Xiag\Rql\Parser\Node\Query\ArrayOperator\OutNode`. Пример: 
             ```php
             $query = new OutNode('name', ['Grayson', 'Lucas']);
             ```
        - Строковое представление: `out`. Пример:
            ```php
            $query = 'out(name,Grayson,Lucas)';

2. Логические операторы.
    * `and`. Оператор, который отображает только те записи, когда все условие является правдой (`true`).
        - Объект: `Xiag\Rql\Parser\Node\Query\LogicOperator\AndNode`.  В качестве параметров принимает ноды. Пример: 
             ```php
             $query = new AndNode([
                 new EqNode('name', 'John'),  
                 new EqNode('surname', 'Smith')  
             ]);
             ```
        - Строковое представление: `and`. Пример:
            ```php
            $query = 'and(eq(name,John),eq(surname,Smith))';
            ```
    * `or`. Оператор, который отображает только те записи, когда хотя бы одно из двух условий является правдой (`true`).
        - Объект: `Xiag\Rql\Parser\Node\Query\ArrayOperator\OrNode`. В качестве параметров принимает ноды. Пример: 
             ```php
             $query = new OrNode([
                 new EqNode('login', 'congrate'),  
                 new EqNode('name', 'John')  
             ]);
             ```
        - Строковое представление: `or`. Пример:
            ```php
            $query = 'or(eq(login,congrate),eq(name,John))';
            ```
    * `not`. Оператор служит для задания противоположно заданного условия.
        - Объект: `Xiag\Rql\Parser\Node\Query\ArrayOperator\NotNode`. В качестве параметров принимает ноды. Пример: 
             ```php
             $query = new NotNode([EqNode('id', '1')]);
             ```
        - Строковое представление: `not`. Пример:
            ```php
            $query = 'not(eq(id,1))';
            ```

3. Бинарные операторы.
    * `eqf`. Оператор служит для сравнение с булевым `false`.
        - Объект: `rollun\datastore\Rql\Node\BinaryNode\EqfNode`. Пример: 
             ```php
             $query = new EqfNode('isActive');
             ```
        - Строковое представление: `eqf`. Пример:
            ```php
            $query = 'eqf(isActive)';
            ```
    * `eqt`. Оператор служит для сравнение с булевым `true`.
        - Объект: `rollun\datastore\Rql\Node\BinaryNode\EqtNode`. Пример: 
             ```php
             $query = new EqtNode('isActive');
             ```
        - Строковое представление: `eqt`. Пример:
            ```php
            $query = 'eqt(isActive)';
            ```
    * `eqn`. Оператор служит для сравнение с `null`.
        - Объект: `rollun\datastore\Rql\Node\BinaryNode\EqnNode`. Пример: 
             ```php
             $query = new EqbNode('name');
             ```
        - Строковое представление: `eqn`. Пример:
            ```php
            $query = 'eqn(name)';
            ```
    * `ie`. Оператор служит для того, чтобы определить является ли значение пустым (равным `false` или `null`).
        - Объект: `rollun\datastore\Rql\Node\BinaryNode\IeNode`. Пример: 
             ```php
             $query = new IeNode('name');
             ```
        - Строковое представление: `ie`. Пример:
            ```php
            $query = 'ie(name)';
            ```
4. Скалярные операторы.
    * `eq`, `ge`, `gt`, `le`, `lt`, `ne` аналогичны операторам `=`, `>=` ,`>` ,`<=` ,`<`, `!=`. Названия операторов 
    эквивалентны их строковым представлениям. Соответствие строкового представления к объектам:
        - `Xiag\Rql\Parser\Node\Query\ScalarOperator\EqNode` для `eq`; 
        - `Xiag\Rql\Parser\Node\Query\ScalarOperator\GeNode` для `ge`; 
        - `Xiag\Rql\Parser\Node\Query\ScalarOperator\GtNode` для `gt`; 
        - `Xiag\Rql\Parser\Node\Query\ScalarOperator\LeNode` для `le`; 
        - `Xiag\Rql\Parser\Node\Query\ScalarOperator\LtNode` для `lt`; 
        - `Xiag\Rql\Parser\Node\Query\ScalarOperator\NeNode` для `ne`.

        Пример:
        ```php
        $query = 'gt(age,21)';
        $query = GtNode('age', '21');
        ```

5. Нода для задания лимита (`limit`) и сдвига (`offset`).
    - Объект: `Xiag\Rql\Parser\Node\LimitNode`. Первый параметр для задание лимита, в второй (необязательный) 
    для задания сдвига. Пример: 
        ```php
        $query = new LimitNode(5, 10);
        ```
    - Строковое представление: `limit`. Пример:
        ```php
        $query = 'limit(5,10)';
        ```

6. Нода для задания агрегирующей функции. Доступные функции: `count`, `max`, `min`, `sum`, `avg`. Используется 
только в сочетании с `AggregateSelectNode`. Объект: `rollun\datastore\Rql\Node\AggregateFunctionNode`.

7. Нода для задания выборки (поля которые нужно считать). Если нода не задана, по умолчанию будут считаны все поля.
    * `SelectNode`.
        - Объект: `Xiag\Rql\Parser\Node\SelectNode`.
        для задания сдвига. Пример: 
            ```php
            $query = new SelectNode(['id', 'name']);
            ```
        - Строковое представление: `select`. Пример:
            ```php
            $query = 'select(id,name)';
            ```
    * `AggregateSelectNode`. Точно такая же нода как и `SelectNode`, за исключением того что в качестве поля может
    принимать агрегирующую ноду.
        - Объект: `rollun\datastore\Rql\Node\AggregateSelectNode`. Пример:
            ```php
            $query = new AggregateSelectNode(['id', new AggregateFunctionNode('count', 'name)]);
            ```
        - Строковое представление аналогично. Пример:
            ```php
            $query = 'select(id,count(name))';
            ```

8. Нода для задания сортировки.
    - Объект: `Xiag\Rql\Parser\Node\SortNode`. Принимает массив, где ключ - поле, значение - `1`(asc) или `-1`(desc) 
    Пример: 
        ```php
        $query = new SortNode(['id' => 1, 'name' => -1]);
        ```
    - Строковое представление: `sort`. Пример:
        ```php
        $query = 'sort(+id,-name)'; // or 'sort(id,-name)'
        ```
        
9. Нода для задания группировки.
    - Объект: `rollun\datastore\Rql\Node\GroupByNode`. Принимает массив полей для группировки.
        ```php
        $query = new GroupbyNode(['name']);
        ```
    - Строковое представление: `ie`. Пример:
        ```php
        $query = 'groupby(name)';
        ```
        
Чтобы задать тип используется структуру типа `{type}:{value}`.

Пример:

```php
<?php

use rollun\datastore\DataStore\Memory;
use rollun\datastore\Rql\RqlQuery;

$dataStore = new Memory(['id', 'name', 'age']);

// You can use string
$rql = new RqlQuery('and(ge(id,1),or(not(eqn(name)),not(eqn(surname))))&limit(1)&select(email,password)');
$dataStore->query($rql);
```

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

`Cleaner` предоставляет гибкий способ удаление данных, которые прошли 'валидацию на удаление' с хранилища. Для этого
нужно реализовать единственный метод `isValid()` интерфейса `CleaningValidatorInterface`.

Пример:

```php
<?php

use rollun\datastore\DataStore\Memory;
use rollun\datastore\Cleaner\Cleaner;
use rollun\utils\Cleaner\CleaningValidator\CleaningValidatorInterface;
use rollun\datastore\Rql\RqlQuery;

$dataStore = new Memory(['id', 'name']);

foreach (range(1, 3) as $id) {
    $dataStore->create([
        'id' => $id,
        'name' => "foo{$id}",
    ]);
}

$cleaningValidator = new class implements CleaningValidatorInterface
{
    public function isValid($value){
        return $value['id'] > 1 && $value['name'] !== 'foo3';
    }
};

$cleaner = new Cleaner($dataStore, $cleaningValidator);
$cleaner->cleanList();

var_dump($dataStore->count()); // 1
var_dump($dataStore->query(new RqlQuery())); // [['id' => '2', 'name' => 'foo2']]

```


### Uploader

Для загрузки данных в хранилище с итератора можно использовать `Uploader`. Так же если этот итератор
поддерживает интерфейс `\SeekableIterator` можно возобновлять загрузку данных с прошлой позиции.
`DataStorePack` реализует итератор `\SeekableIterator` для хранилища. Таким способом можно 
перекачивать данные с одного хранилища в другой, с возможностью возобновить загрузку данных с прошлой считаной позиции.

Пример:

```php
<?php

use rollun\datastore\DataStore\Memory;
use rollun\uploader\Uploader;
use rollun\uploader\Iterator\DataStorePack;

$dataStoreFrom = new Memory(['id', 'name']);

foreach (range(1, 4) as $id) {
    $dataStoreFrom->create([
        'id' => $id,
        'name' => "foo{$id}"
    ]);
}

$dataStoreTo = new Memory(['id', 'name']);

$iterator = new DataStorePack($dataStoreFrom);
$uploader = new Uploader($iterator, $dataStoreTo);

var_dump($dataStoreTo->has(1)); // false

$uploader->upload(); // or $uploader();

var_dump($dataStoreTo->has(1)); // true
var_dump($dataStoreTo->read(1)); // ['id' => 1, 'name' => 'foo1']
var_dump($dataStoreTo->read(3)); // ['id' => 3, 'name' => 'foo3']

```


### Data store factories

Библиотека предоставляет соответствующие фабрики к базовым реализациям `DataStoresInterface`, которые можно задать для
[zendframework/zend-servicemanager](https://github.com/zendframework/zend-servicemanager).
Все фабрики имеют единый ключ верхнего уровня `dataStore` (`DataStoreAbstractFactory::KEY_DATASTORE`).

##### 1. `DbTableAbstractFactory`

Пример конфигурации:
```php
[
    'dataStore' => [
        'serviceName1' => [
            'class' => \rollun\datastore\DataStore\DbTable::class,
            'tableName' => 'myTableName',
            'dbAdapter' => 'db' // service name, optional
            'sqlQueryBuilder' => 'sqlQueryBuilder' // service name, optional
        ],
        'serviceName2' => [
            // ...
        ],
    ]
]
```

##### 2. `CsvAbstractFactory`

Пример конфигурации:
```php
[
    'dataStore' => [
        'serviceName1' => [
            'class' => \rollun\datastore\DataStore\CsvBase::class,
            'filename' => 'someFile',
            'delimiter' => ',' // optional
        ],
        'serviceName2' => [
            // ...
        ],
    ]
]
```

##### 3. `HttpClientAbstractFactory`

Пример конфигурации:
```php
[
    'dataStore' => [
        'serviceName1' => [
            'class' => 'rollun\datastore\DataStore\HttpDatastoreClassName',
            'url' => 'http://site.com/api/resource-name', // general url scheme: {scheme}://{host}[:{port}]/api/{sourceName}
            'options' => [
                'timeout' => 30,
                'adapter' => 'Zend\Http\Client\Adapter\Socket',
            ]
        ],
        'serviceName2' => [
            // ...
        ],
    ]
]
```

##### 4. `MemoryAbstractFactory`

Пример конфигурации:
```php
[
    'dataStore' => [
        'serviceName1' => [
            'class' => 'rollun\datastore\DataStore\Memory',
            'requiredColumns' => [, // optional
                'column1',
                'column2',
                // ...
            ],
        ],
        'serviceName2' => [
            // ...
        ]
    ]
]
```

##### 5. `CacheableAbstractFactory`

Пример конфигурации:
```php
[
    'dataStore' => [
        'serviceName1' => [
            'class' => \rollun\datastore\DataStore\Cacheable::class,
            'dataSource' => 'testDataSourceDb',
            'cacheable' => 'testDbTable'
        ],
        'serviceName2' => [
            // ...
        ]
    ]
]
```

#### Apects, EventManager
Библиотека поддерживает aspect pattern и observer pattern(Zend EventManager), что дает возможность выполнять дополнительные действие до события или после.

Пример конфигурации для оборачивания датастора в аспект с eventManager:
```php
[
        'dataStore' => [
            'aspectDataStore1' => [ // оборачивает dataStore1 в аспект
                'class'     => \rollun\datastore\DataStore\Aspect\AspectAbstract::class, //  здесь указываем класс в котором будут методы аспекта, например preCreate
                'dataStore' => 'dataStore1' // указывем dataStore
            ],
            'aspectDataStore2' => [
                'class'     => \rollun\datastore\DataStore\Aspect\AspectWithEventManagerAbstract::class, // оборачиваем dataStore1 в аспект с event manager
                'dataStore' => 'dataStore1',
                'listeners' => [ // указывем слушатели 
                    \App\Listener\DataStoreMasterListener::class, // указывем класс которые наследник \rollun\datastore\DataStore\Aspect\AbstractAspectListener
                    // или
                    'onPostCreate' => ['postCreateUpdateHandler'], //  здесь нужно указать callable 
                ]
            ],
            'dataStore1'       => [
                'class'     => \rollun\datastore\DataStore\DbTable::class,
                'tableName' => 'datastore_1',
            ],
        ],
]
```

Пример слушателя:
```php
<?php
declare(strict_types=1);

namespace App\Listener;

use rollun\datastore\DataStore\Aspect\AbstractAspectListener;
use rollun\datastore\DataStore\DbTable;
use rollun\dic\InsideConstruct;
use Zend\EventManager\Event;

/**
 * Class DataStoreMasterListener
 *
 * @author Roman Ratsun <r.ratsun.rollun@gmail.com>
 */
class DataStoreMasterListener extends AbstractAspectListener
{
    /**
     * @var DbTable
     */
    private $dataStore;

    /**
     * DataStoreMasterListener constructor.
     *
     * @throws \ReflectionException
     */
    public function __construct()
    {
        InsideConstruct::init(['dataStore' => 'dataStore2']);
    }

    /**
     * @param Event $event
     */
    public function onPostCreate(Event $event)
    {
       // реализация
    }

     /**
     * @inheritDoc
     */
    public function __sleep()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function __wakeup()
    {
        InsideConstruct::initWakeup(['dataStore' => 'dataStore2']);
    }
}
```

#### FileObject
Библиотека предоставляет объект (расширяет SplFileObject) для работы с файлами. Преимущества данного объекта в том, что здесь реализованы блокировки файлов, что существенно упростят работу.
Пример использования:
```php
<?php
use rollun\files\FileObject;

$fileObject = new FileObject('some-file.csv');
$fileObject->fwriteWithCheck('012345');
$fileObject->moveSubStr(3, 1);
$fileObject->fseek(0);
$actual = $fileObject->fread(100); // '0345'
``` 
Для более подробного изучения ознакомьтесь с юнит [тестами](../test/unit/Files/FileObject).

#### CsvFileObject, CsvFileObjectWithPrKey
Библиотека предоставляет объекты для работы с csv файлами. CsvFileObjectWithPrKey работает с файлами используя разные стратегии. По умолчанию используется стратегия бинарного поиска, что в разы ускоряет поиск нужной нам строки.
Пример использования:
```php
<?php
use rollun\files\Csv\CsvFileObjectWithPrKey;

$fileObject = new CsvFileObjectWithPrKey('some-file.csv');
$result = $fileObject->getRowById('1'); // array

``` 
Для более подробного изучения ознакомьтесь с юнит [тестами](../test/unit/Files/CsvFileObject).