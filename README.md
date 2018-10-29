# datastore

---
## [Оглавление](https://github.com/rollun-com/rollun-skeleton/blob/master/docs/Contents.md)

---

Каркас для создания приложений. 

* [Детальная документация](doc/)

* [zaboy Rql](https://github.com/rollun-com/rollun-datastore/blob/master/doc/RQL_PARSER.md)

* [Запуск тестов](https://github.com/rollun-com/rollun-datastore/blob/master/doc/TESTS.md)

* [DataStore Абстрактные фабрики](https://github.com/rollun-com/rollun-datastore/blob/master/doc/DataStore%20Abstract%20Factory.md)

* [Стандарты](https://github.com/rollun-com/rollun-skeleton/blob/master/docs/Standarts.md)

## Запуск тестов

Установите переменную окружения `'APP_ENV' = "dev"`.
Так же добавте переменную окружение `HOST` в которую поместите ip или домен вашего приложения
> Или добавте данные переменную в файл `env_config.php`.

Скопируйте `index.php`и .htaccess из библиотеки в паблик директорию проекта.

Запустите скрипт `composer lib-install`, он создаст таблицы в базе.

# Использование библиотеки

Что бы использовать данную библиотеку в своих приложениях следуйте [данной инструкции](INSTALL.md)


# Data Type

Каждая реализация data store должна быть типизированная и уметь возвращать данные о столбцах и их типах.
Для введения типа используются объекты, которые реализуют интерфейс TypeInterface.

Пример:

```php
<?php

namespace Module\Type;

use rollun\datastore\DataStore\Type\TypeInterface;

final class TypeInt implements TypeInterface
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function toTypeValue()
    {
        return (int)$this->value;
    }
}

final class TypeString implements TypeInterface
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function toTypeValue()
    {
        return (string)$this->value;
    }
}
```

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
use Module\Type\TypeInt;
use Module\Type\TypeString;

class User extends BaseDto
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

echo $user->getId(); // 1 (int)
echo $user->getName(); // 'name' (string)
```

Зачастую data store нужно передать отформатированные каким то образом данные полученные из DTO. 
Для этого удобно использовать форматеры. Форматеры (formatter) реализуют интерфейс FormatterInterface.

Пример:

```php
<?php

namespace Module\Formatter;

use rollun\datastore\DataStore\Formatter\FormatterInterface;
use Module\Dto\User;

class StringFormatter implements FormatterInterface
{
    public function format($value)
    {
        return (string)$value;
    }
}

// The same as 'new UserDto($id, $name)'
$user = UserDto::createInstance([
    'id' => $id,
    'name' => $name
]);
```

Типизацией и хранением данных о себе data store занимается аспект data store который поддерживет интерфейс SchemableInterface
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
