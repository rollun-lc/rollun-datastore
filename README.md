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

