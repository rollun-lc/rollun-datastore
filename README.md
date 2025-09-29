# rollun-datastore

`rollun-datastore` - это библиотека, которая предоставляет единый интерфейс взаимодействие с любым хранилищем данных
на основе [Resource Query Language (RQL)](https://www.sitepen.com/blog/2010/11/02/resource-query-language-a-query-language-for-the-web-nosql/).
Существующие реализации: DbTable (для таблицы бд), CsvBase (для csv файлов), HttpClient (для внешнего ресурса через 
http), Memory (для [RAM](https://en.wikipedia.org/wiki/Random-access_memory)).

* [Документация](docs/index.md)
* Опис історії змін по версіям [CHANGELOG.md](CHANGELOG.md)
* [Поддерживаемые запросы к датасторам и их методы](https://docs.google.com/spreadsheets/d/1UknTHmrL8HaCDPefSGKUoMysKwInPynrTOQwRd62e2U/edit?usp=sharing)

## Краткая таблица операций

| Операция | HTTP | RQL | Тело запроса | Примечания                                                                                                |
|---|---|---|---|-----------------------------------------------------------------------------------------------------------|
| Получить запись (`read`) | GET | Нет | — | Требуется ID                                                                                              |
| Поиск (`query`) | GET | **Обязателен** | — | RQL в query-string.                                                                                       |
| Скачать CSV | GET | Можно | — | `Accept: text/csv`; лимит переопределяется                                                                |
| Создать (`create`) | POST | Нет | Объект | При дубле ID — ошибка, если `overwrite=false`.                                                            |
| Множественное создание (`multiCreate`) | POST | Нет | **Массив** объектов | Если в объекте датасторе не реализован метод, циклически выполняется create                               |
| Обновить (`update`) | PUT | Нет | Объект | ID обязателен; если есть и в path, и в body — приоритет у body; допускается «создать, если нет» по флагу. |
| Обновить по фильтру (`queriedUpdate`) | PATCH | **Обязателен** | Объект | **Нужен `limit`**; без `select`/`groupBy`; без PK в body.                                                 |
| Удалить (`delete`) | DELETE | Нет | — | Требуется ID.                                                                                             |
| HEAD (`getIdentifier`) | HEAD | Нет | — | Возвращает метаданные/хедеры по PK.                                                                       |
| Refresh (если поддерживается) | PATCH | Нет | — | Для датасторов с `RefreshableInterface`.                                                                  |


### Для сторонних клиентов

В DataStore используется дополненная версия `rawurlencode`.
К перечню стандартных символов добавлены следующие преобразования

* `-` => `%2D`
* `_` => `%5F`
* `.` => `%2E`
* `~` => `%7E`
