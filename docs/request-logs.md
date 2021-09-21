# Логирование запросов

Для датасторов на основе базы данных (`DbTable`) были добавлены логи для CRUD операций.

Включать логирование нужно для каждого датастора отдельно, чтобы это сделать, нужно добавить в конфиг опцию `DataStoreAbstractFactory::KEY_WRITE_LOGS => true`.

Пример:
```
    'dataStore' => [
        AccSourceMarketplace::class => [
            DbTableAbstractFactory::KEY_CLASS => AccSourceMarketplace::class,
            DbTableAbstractFactory::KEY_TABLE_GATEWAY => AccSourceMarketplace::TABLE_NAME,
            DataStoreAbstractFactory::KEY_WRITE_LOGS => true,
        ],
    ]
```

Логи пишутся с уровнем `debug`, поэтому чтобы отключить все логи сразу, можно использовать фильтр логгера по уровню.

Если для датастора определен класс, наследующий `DbTable`, то для работы логов нужно добавить параметр `$writeLogs` в конструктор и передать его в родительский конструктор.

Пока что логи реализованы только для датасторов на основе базы данных (`DbTable`), возможно позже будут добавлены и для других.