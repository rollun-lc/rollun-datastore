# Project Overview

## Project Name

rollun-datastore

## Purpose

PHP библиотека, предоставляющая единый интерфейс для работы с различными хранилищами данных через Resource Query Language (RQL).

## Executive Summary

Проект реализует набор модульных компонентов (DataStore, Repository, Uploader) и предоставляет как библиотечный API, так и опциональный HTTP-миддлвар для REST-доступа. Конфигурация осуществляется через Laminas ConfigAggregator, а зависимости управляются Composer.

## Tech Stack Summary

- PHP ^8.0
- Composer
- Laminas components (ServiceManager, Db, Diactoros, Stratigility)
- PHPUnit
- Docker / Docker Compose

## Architecture Type

Модульная библиотека с DI-контейнером (Laminas ServiceManager) и конфигурацией через ConfigAggregator.

## Repository Structure

Monolith, 1 part (root)

## Documentation Links

- Architecture: `architecture.md`
- Source Tree: `source-tree-analysis.md`
- Development Guide: `development-guide.md`
- Deployment Guide: `deployment-guide.md`
- Existing docs: `docs/` (index and topic pages)
