# rollun-datastore

`rollun-datastore` - это библиотека, которая предоставляет единый интерфейс взаимодействие с любым хранилищем данных
на основе [Resource Query Language (RQL)](https://www.sitepen.com/blog/2010/11/02/resource-query-language-a-query-language-for-the-web-nosql/).
Существующие реализации: DbTable (для таблицы бд), CsvBase (для csv файлов), HttpClient (для внешнего ресурса через 
http), Memory (для [RAM](https://en.wikipedia.org/wiki/Random-access_memory)).

* [Документация](docs/index.md)
* [Поддерживаемые запросы к датасторам и их методы](https://docs.google.com/spreadsheets/d/1UknTHmrL8HaCDPefSGKUoMysKwInPynrTOQwRd62e2U/edit?usp=sharing)

### Для сторонних клиентов

В DataStore используется дополненная версия `rawurlencode`.
К перечню стандартных символов добавлены следующие преобразования

* `-` => `%2D`
* `_` => `%5F`
* `.` => `%2E`
* `~` => `%7E`
