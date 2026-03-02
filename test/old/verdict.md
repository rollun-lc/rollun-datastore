# Вердикт по старым тестам

## Управление таблицами

Старые тесты используют методы `_prepareTable` и `_getDbTableFields` для создания/удаления MySQL-таблиц через raw SQL. Эти методы продублированы в 5 классах: `DbTableTest`, `DbTableMultiInsertTest`, `SerializedDbTableTest`, `HttpClientTest`, `CacheableTest`. Логика одинаковая — анализ PHP-типов данных и генерация DDL (string → `CHAR(80)`, int → `INT AUTO_INCREMENT`, float → `DOUBLE PRECISION` и т.д.).

В новых тестах для этих целей используется `TableManagerMysql` с декларативным конфигом полей (`field_type`, `field_params`, `field_primary_key`). Исключение — `ContainsUnderScoreTest`, который использует raw SQL через `adapter->query()` для нестандартной схемы таблицы.

**Предложение:** использовать `TableManagerMysql` для типовых сценариев, а raw SQL оставить как fallback для нестандартных случаев. Возможно, стоит расширить `TableManagerMysql` методом для произвольного SQL — тогда можно будет полностью перейти на `TableManagerMysql` без потери гибкости.

## Покрытие тестовых сценариев

Базовые CRUD- и query-сценарии присутствуют и в старых, и в новых тестах:

- **Старые:** абстрактный `AbstractTest` (~45 методов) — CRUD, query, sort, limit/offset, aggregation, group by, select, like/glob, null-handling. Наследуется в 7 тестах: Memory, Csv, CsvIntId, DbTable, SerializedDbTable, HttpClient, Cacheable, Aspect.
- **Новые:** абстрактный `BaseDataStoreTest` (~22 метода) — аналогичный набор CRUD/query/aggregation. Наследуется в Memory, Csv, CsvIntId, DbTable, SerializedDbTable, HttpClient. Плюс специализированные абстрактные классы: `DateField/BaseTest`, `StringNotEqualsTest/BaseTest`, `ConnectionExceptionTest/BaseTest`, `OperationTimedOutExceptionTest/BaseTest`.

### Уникальные кейсы старых тестов, отсутствующие в новых

- **`MemoryTest`** — тесты безопасности: SQL-injection через query, sort, field (exploit-тесты).
- **`DbTableMultiInsertTest`** — bulk-insert 20 000 записей (с ID, без ID, с перезаписью).
- **`CacheableTest`** — 8 тестов кеширующей обёртки (create/update/delete с cache refresh, работа с двумя таблицами).
- **`HttpClientTest`** — тесты HTTP-заголовков Content-Range при limit/offset.
- **`CsvIntIdTest`** — проверка целостности данных (сортировка ID, отклонение не-integer ключей).
- **`RqlQueryTest`** — парсинг RQL-строк (query nodes, sort, select, limit).
- **Factory-тесты** — проверка DI-фабрик для Memory, DbTable, Cacheable, TableGateway, TableManagerMysql.

**Предложение:** типовые CRUD/query-сценарии из `AbstractTest` уже покрыты в `BaseDataStoreTest` — дублировать не нужно. Уникальные кейсы (exploit-тесты, bulk-insert, Cacheable, Content-Range, RQL-парсер, фабрики) перенести в отдельные тест-классы новой структуры.
