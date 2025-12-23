---
stepsCompleted: [1, 2, 3]
inputDocuments:
  - _bmad-output/prd.md
  - _bmad-output/index.md
  - _bmad-output/project-overview.md
  - _bmad-output/architecture.previous.md
  - _bmad-output/source-tree-analysis.md
  - _bmad-output/component-inventory.md
  - _bmad-output/development-guide.md
  - _bmad-output/deployment-guide.md
  - docs/index.md
  - docs/datastore-schema.md
  - docs/datastore_methods.md
  - docs/request-logs.md
  - docs/typecasting.md
  - docs/rql.md
  - src/DataStore/src/DataStore/Scheme/README.md
workflowType: 'architecture'
lastStep: 0
project_name: 'rollun-datastore'
user_name: 'Iliya'
date: '2025-12-23T10:47:26Z'
---

# Architecture Decision Document

_This document builds collaboratively through step-by-step discovery. Sections are appended as we work through each architectural decision together._

## Project Context Analysis

### Requirements Overview

**Functional Requirements:**
- Сохранить обратную совместимость через DataStoreInterface и поведенческие контракты.
- Обеспечить корректный парсинг RQL и типизацию без double-encoding.
- MVP: каркас DDD + Clean Architecture с реализацией read для MemoryDataStore.
- Тесты на уровне модулей для предотвращения регрессий.
- Краткая документация и инструкция миграции без изменений кода.

**Non-Functional Requirements:**
- Надежность: отсутствие регрессий в RQL, типизации и поведении запросов.
- Поддерживаемость: четкие границы модулей.
- Тестируемость: полное модульное покрытие.

**Scale and Complexity:**
- Основной домен: backend PHP библиотека.
- Уровень сложности: низкий.
- Оценка компонентов: 3 модуля (DataStore, Repository, Uploader) + RQL и типы.

### Technical Constraints and Dependencies

- PHP 8.x, установка через Composer.
- Обязательная обратная совместимость DataStoreInterface.
- Архитектурное требование: DDD + Clean Architecture.
- MVP ограничен MemoryDataStore read при расширяемой архитектуре.

### Cross-Cutting Concerns Identified

- Обратная совместимость публичных контрактов.
- Единое поведение RQL и типизации.
- Тесты по модулям для защиты от регрессий.
- Документация и миграция синхронизированы с реальным поведением.

## Starter Template Evaluation

### Primary Technology Domain

Backend PHP library (Composer package) with Laminas components; optional HTTP layer is not the primary deliverable.

### Starter Options Considered

- **laminas/laminas-mvc-skeleton**: MVC‑приложение на Laminas. Удобно для app‑проекта, но избыточно для библиотеки.
- **mezzio/mezzio-skeleton**: PSR‑15 middleware‑приложение. Подходит для HTTP‑слоя, но не для библиотечного ядра.

### Selected Starter: None (custom library baseline)

**Rationale for Selection:**
- Проект — библиотека с DDD/Clean Architecture, а не web‑app.
- Использование app‑скелета навяжет слои и структуру, не соответствующие целевому продукту.
- Сохраняем текущую структуру и строим архитектуру модульно внутри библиотеки.

**Initialization Command:**

```bash
# No starter template. Use existing repository structure.
# For local setup, use:
composer install
```

**Architectural Decisions Provided by Starter:**

**Language & Runtime:**
- PHP 8.x (existing project constraint)

**Styling Solution:**
- Not applicable (library)

**Build Tooling:**
- Composer, existing tooling (CSFixer, Rector, Psalm, PHPStan)

**Testing Framework:**
- PHPUnit (existing tooling)

**Code Organization:**
- Module‑oriented library structure (DataStore/Repository/Uploader)

**Development Experience:**
- Existing Docker/Makefile flows where needed

**Note:** Project initialization is based on the current repository; no create‑project starter is used.
