# datastore

---
## [Оглавление](https://github.com/avz-cmf/Server-Drakon/blob/master/Table%20of%20contents.md)

---

Каркас для создания приложений. 

* [Quickstart](https://github.com/avz-cmf/saas/blob/master/docs/Quickstart.md)
* [Детальная документация](doc/)
* [zaboy Rql](https://github.com/avz-cmf/zaboy-rest/blob/master/doc/RQL_PARSER.md)
* [Запуск тестов](https://github.com/avz-cmf/zaboy-rest/blob/master/doc/TESTS.md)
* [DataStore Абстрактные фабрики](https://github.com/avz-cmf/zaboy-rest/blob/master/doc/DataStore%20Abstract%20Factory.md)
* [EAV](https://github.com/avz-cmf/zaboy-rest/blob/master/doc/EAVDataStore.md)
* [EAV примеры](https://github.com/avz-cmf/zaboy-rest/blob/master/doc/EAV%20example.md)
* [Composite](https://github.com/avz-cmf/zaboy-rest/blob/master/doc/Composite.md)
* [Стандарты](https://github.com/avz-cmf/zaboy-skeleton/blob/master/docs/Standarts.md)

## Запуск тестов

Установите переменную окружения `'APP_ENV' = "dev"`;

Перед тем как запускать тесты, создайте файл `test.local.php` в `config/autoload`
и добавьте туда настройки для `httpDataStore` изменив localhost в параметре url так что бы по нему можно было получить доступ к веб-приложению.

Пример:

 ```php
    return [
        "dataStore" => [
            'testHttpClient' => [
                'class' => 'zaboy\rest\DataStore\HttpClient',
                'tableName' => 'test_res_http',
                'url' => 'http://localhost/api/rest/test_res_http',
                'options' => ['timeout' => 30]
            ],
            'testEavOverHttpClient' => [
                'class' => 'zaboy\rest\DataStore\HttpClient',
                 'url' => 'http://localhost/api/rest/entity_product',
                 'options' => ['timeout' => 30]
            ],
            'testEavOverHttpDbClient' => [
                        'class' => 'zaboy\rest\DataStore\HttpClient',
                        'url' => 'http://localhost:9090/api/rest/db'. EavAbstractFactory::DB_NAME_DELIMITER . 'entity_product',
                        'options' => ['timeout' => 30]
                   ],
        ]
    ];
 ```

Скопируйте `index.php`и .htaccess из библиотеки в паблик директорию проекта.

Запустите скрипт `composer lib-install`, он создаст таблицы в базе.

# Использование библиотеки

Что бы использовать данную библиотеку в своих приложениях следуйте [данной инструкции](INSTALL.md)

