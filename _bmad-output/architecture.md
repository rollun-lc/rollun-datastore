# Architecture

## Executive Summary

`rollun-datastore` — PHP библиотека для унифицированного доступа к хранилищам данных с RQL-выражениями. Проект организован как модульная библиотека с конфигурацией через Laminas ServiceManager и опциональным HTTP-слоем (middleware) для REST доступа.

## Technology Stack

- **Language:** PHP ^8.0
- **Dependency Manager:** Composer
- **Core Framework/Components:** Laminas (ServiceManager, Db, Diactoros, Stratigility), Mezzio (dev)
- **Testing:** PHPUnit
- **Tooling:** PHP-CS-Fixer, PHP_CodeSniffer, Rector
- **Runtime/Infra:** Docker, Docker Compose

## Architecture Pattern

- **Style:** Модульная библиотека с DI-контейнером и конфигурацией через ConfigAggregator.
- **Modules:** DataStore, Repository, Uploader (каждый как отдельный namespace с ConfigProvider).
- **Entry (optional):** `public/index.php` для HTTP-интеграции.

## Data Architecture

- Для типа проекта *library* отдельная схема БД не фиксируется в коде.
- Взаимодействие с БД конфигурируется через `config/autoload/db.global.php` и таблицы/гейтвеи в `config/autoload/rollun.datastore.Asset*.php`.

## API Design

- API как отдельный сервис не выделен; библиотека предоставляет middleware и обработчики для REST-доступа (DataStore middleware).
- Протокол запросов описан в существующей документации (см. `docs/datastore_methods.md`).

## Component Overview

- **DataStore:** ядро датастора (RQL, схемы, типы, форматтеры, middleware, table gateways).
- **Repository:** модельные абстракции, кастинг и репозиторий.
- **Uploader:** загрузчик и итераторы пакетов.

## Source Tree (High-Level)

- `src/DataStore/src` — datastore core
- `src/Repository/src` — repository core
- `src/Uploader/src` — uploader core
- `config/` — конфигурация модулей и DI
- `public/` — опциональный HTTP entry
- `docs/` — документация
- `test/` — тесты

## Development Workflow

- `make init` / `make init-8.0` — поднять окружение и установить зависимости
- `make test` — запустить тесты в контейнере
- `make lint`, `make fixcs`, `make rector` — качество кода

## Deployment Architecture

- Docker Compose: nginx + php-fpm + mysql + csfixer
- Конфиги в `docker/` и `docker-compose*.yml`

## Testing Strategy

- Unit/functional/integration тесты в `test/`
- Запуск через `composer test` или `make test`
